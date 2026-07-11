<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImageStatus;
use App\Enums\OffenceStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Offence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OffenceSyncController extends Controller
{
    /**
     * Push a batch of offences from the device outbox. Idempotent: the same
     * UUID arriving twice (e.g. a retried request) updates rather than dupes.
     */
    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'offences'                        => ['required', 'array', 'max:200'],
            'offences.*.id'                   => ['required', 'uuid'],
            'offences.*.device_id'            => ['nullable', 'string', 'max:100'],
            'offences.*.offence_type'         => ['required', 'string', 'max:100'],
            'offences.*.offence_description'  => ['nullable', 'string'],
            'offences.*.vehicle_plate'        => ['nullable', 'string', 'max:20'],
            'offences.*.vehicle_color'        => ['nullable', 'string', 'max:30'],
            'offences.*.vehicle_make'         => ['nullable', 'string', 'max:50'],
            'offences.*.vehicle_type'         => ['nullable', 'string', 'max:30'],
            'offences.*.driver_gender'        => ['nullable', 'in:male,female,unknown'],
            'offences.*.driver_name'          => ['nullable', 'string', 'max:120'],
            'offences.*.driver_fled'          => ['boolean'],
            'offences.*.latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'offences.*.longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'offences.*.location_description' => ['nullable', 'string', 'max:255'],
            'offences.*.occurred_at'          => ['required', 'date'],
            'offences.*.captured_at'          => ['nullable', 'date'],
            'offences.*.images'               => ['array'],
            'offences.*.images.*.id'          => ['required_with:offences.*.images', 'uuid'],
            'offences.*.images.*.sha256_hash' => ['required_with:offences.*.images', 'string', 'size:64'],
            'offences.*.images.*.mime_type'   => ['nullable', 'string', 'max:100'],
            'offences.*.images.*.file_size'   => ['nullable', 'integer', 'min:0'],
            'offences.*.images.*.latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'offences.*.images.*.longitude'   => ['nullable', 'numeric', 'between:-180,180'],
            'offences.*.images.*.captured_at' => ['nullable', 'date'],
            'offences.*.images.*.device_id'   => ['nullable', 'string', 'max:100'],
        ]);

        $officer = $request->user();
        $results = [];

        foreach ($data['offences'] as $payload) {
            $results[] = $this->syncOne($payload, $officer);
        }

        return response()->json([
            'results'   => $results,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    private function syncOne(array $payload, $officer): array
    {
        return DB::transaction(function () use ($payload, $officer) {
            $existing = Offence::withTrashed()->lockForUpdate()->find($payload['id']);

            // A record with this id already exists but belongs to someone else.
            if ($existing && $existing->officer_id !== $officer->id) {
                return ['id' => $payload['id'], 'status' => 'rejected', 'reason' => 'not_owner'];
            }

            // The server has already moved this past submission (a supervisor
            // acted on it) — the record is read-only now. But evidence can
            // still arrive: register any NEW image metadata so the device can
            // upload photos even for an already-reviewed record.
            if ($existing && ! in_array($existing->status, [OffenceStatus::Draft, OffenceStatus::Submitted], true)) {
                $pendingImages = $this->registerImages($existing, $payload['images'] ?? [], $officer);

                return [
                    'id'               => $existing->id,
                    'status'           => 'conflict',
                    'reference_number' => $existing->reference_number,
                    'server_status'    => $existing->status->value,
                    'version'          => $existing->version,
                    'pending_images'   => $pendingImages,
                ];
            }

            $offence = $existing ?? new Offence(['id' => $payload['id']]);

            // Only client-owned fields. officer_id ALWAYS comes from the token,
            // never the payload — an officer can't attribute a record to another.
            $offence->fill([
                'device_id'            => $payload['device_id'] ?? $offence->device_id,
                'offence_type'         => $payload['offence_type'],
                'offence_description'  => $payload['offence_description'] ?? null,
                'vehicle_plate'        => $payload['vehicle_plate'] ?? null,
                'vehicle_color'        => $payload['vehicle_color'] ?? null,
                'vehicle_make'         => $payload['vehicle_make'] ?? null,
                'vehicle_type'         => $payload['vehicle_type'] ?? null,
                'driver_gender'        => $payload['driver_gender'] ?? null,
                'driver_name'          => $payload['driver_name'] ?? null,
                'driver_fled'          => $payload['driver_fled'] ?? false,
                'latitude'             => $payload['latitude'] ?? null,
                'longitude'            => $payload['longitude'] ?? null,
                'location_description' => $payload['location_description'] ?? null,
                'occurred_at'          => $payload['occurred_at'],
                'captured_at'          => $payload['captured_at'] ?? null,
            ]);

            $offence->officer_id = $officer->id;
            $offence->synced_at  = now();

            $isNew = ! $existing;
            if ($isNew) {
                $offence->status           = OffenceStatus::Submitted;
                $offence->version          = 1;
                $offence->reference_number = $this->nextReferenceNumber();
            } else {
                $offence->version = $existing->version + 1;
            }

            $offence->save();

            // Register image metadata rows as "pending" — files arrive separately.
            $pendingImages = $this->registerImages($offence, $payload['images'] ?? [], $officer);

            AuditLog::record($isNew ? 'offence.created' : 'offence.updated', $offence);

            return [
                'id'               => $offence->id,
                'status'           => 'accepted',
                'reference_number' => $offence->reference_number,
                'server_status'    => $offence->status->value,
                'version'          => $offence->version,
                'pending_images'   => $pendingImages,
            ];
        });
    }

    /**
     * Register image metadata rows as "pending" for an offence — the binary
     * files arrive separately via the upload endpoint. Shared by the accepted
     * path AND the conflict path, so evidence can always reach the server even
     * for a record a supervisor has already reviewed. Never touches an
     * already-verified photo.
     */
    private function registerImages(Offence $offence, array $images, $officer): array
    {
        $pendingImages = [];

        foreach ($images as $img) {
            $image = $offence->images()->withTrashed()->find($img['id'])
                ?? $offence->images()->make(['id' => $img['id']]);

            if ($image->exists && $image->status === ImageStatus::Verified) {
                continue;
            }

            $image->fill([
                'officer_id'  => $officer->id,
                'device_id'   => $img['device_id'] ?? null,
                'sha256_hash' => strtolower($img['sha256_hash']),
                'mime_type'   => $img['mime_type'] ?? null,
                'file_size'   => $img['file_size'] ?? null,
                'latitude'    => $img['latitude'] ?? null,
                'longitude'   => $img['longitude'] ?? null,
                'captured_at' => $img['captured_at'] ?? now(),
                'status'      => ImageStatus::Pending,
            ]);
            $image->offence_id = $offence->id;
            $image->save();

            $pendingImages[] = $image->id;
        }

        return $pendingImages;
    }

    /**
     * Pull the officer's own records changed since a timestamp, so the app can
     * absorb assigned reference numbers and supervisor status changes.
     */
    public function pull(Request $request): JsonResponse
    {
        $officer = $request->user();

        $query = Offence::with('images')->where('officer_id', $officer->id);

        if ($since = $request->query('since')) {
            $query->where('updated_at', '>', Carbon::parse($since));
        }

        $offences = $query->orderBy('updated_at')->limit(500)->get();

        return response()->json([
            'offences'  => $offences,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * OFF-{year}-{6-digit sequence}. Runs inside the push transaction with the
     * offence row locked; reference_number is unique, so a rare race just errors
     * and the client's idempotent retry resolves it.
     */
    private function nextReferenceNumber(): string
    {
        $prefix = 'OFF-'.now()->year.'-';

        $last = Offence::withTrashed()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('reference_number')
            ->value('reference_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
