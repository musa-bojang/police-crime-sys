<?php

namespace App\Http\Controllers\Api;

use App\Enums\WatchlistStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sighting;
use App\Models\WatchlistVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    /**
     * Return the active watchlist for the app to cache on the device.
     * Only active entries and only the fields the app needs for matching and
     * for showing the alert.
     */
    public function index(): JsonResponse
    {
        $vehicles = WatchlistVehicle::where('status', WatchlistStatus::Active)
            ->get([
                'id', 'plate', 'plate_normalized', 'vehicle_make',
                'vehicle_color', 'vehicle_type', 'reason', 'severity',
                'instructions', 'updated_at',
            ]);

        return response()->json([
            'watchlist' => $vehicles,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Record a sighting: an officer's plate check matched this wanted vehicle.
     * Captures who, where, and when — the tracking signal.
     */
    public function sighting(Request $request, WatchlistVehicle $vehicle): JsonResponse
    {
        $data = $request->validate([
            'plate_checked' => ['nullable', 'string', 'max:20'],
            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],
            'device_id'     => ['nullable', 'string', 'max:100'],
            'sighted_at'    => ['nullable', 'date'],
        ]);

        $sighting = Sighting::create([
            'watchlist_vehicle_id' => $vehicle->id,
            'officer_id'           => $request->user()->id,
            'plate_checked'        => $data['plate_checked'] ?? $vehicle->plate,
            'latitude'             => $data['latitude'] ?? null,
            'longitude'            => $data['longitude'] ?? null,
            'device_id'            => $data['device_id'] ?? $request->header('X-Device-Id'),
            'sighted_at'           => $data['sighted_at'] ?? now(),
        ]);

        AuditLog::record('watchlist.sighting', $vehicle, [
            'sighting_id' => $sighting->id,
        ]);

        return response()->json([
            'id'         => $sighting->id,
            'recorded'   => true,
            'sighted_at' => $sighting->sighted_at->toIso8601String(),
        ], 201);
    }
}
