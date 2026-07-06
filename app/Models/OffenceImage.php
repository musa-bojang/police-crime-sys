<?php

namespace App\Models;

use App\Enums\ImageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class OffenceImage extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'offence_id',
        'officer_id',
        'device_id',
        'file_path',
        'thumbnail_path',
        'original_filename',
        'mime_type',
        'file_size',
        'width',
        'height',
        'sha256_hash',
        'hash_verified_at',
        'latitude',
        'longitude',
        'captured_at',
        'uploaded_at',
        'status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'file_size'        => 'integer',
            'width'            => 'integer',
            'height'           => 'integer',
            'hash_verified_at' => 'datetime',
            'latitude'         => 'decimal:8',
            'longitude'        => 'decimal:8',
            'captured_at'      => 'datetime',
            'uploaded_at'      => 'datetime',
            'version'          => 'integer',
            'status'           => ImageStatus::class,
        ];
    }

    /**
     * Write-once guard: once an image is Verified, its evidentiary fields
     * (the file, hash, and capture metadata) can never change. Only the
     * status may still move (e.g. Verified -> Quarantined if later flagged).
     * This is the application-level immutability the DB can't enforce.
     */
    protected static function booted(): void
    {
        static::updating(function (OffenceImage $image) {
            $original = $image->getOriginal('status');

            if ($original === ImageStatus::Verified->value) {
                $locked = [
                    'file_path', 'sha256_hash', 'captured_at',
                    'latitude', 'longitude', 'file_size', 'offence_id',
                ];

                foreach ($locked as $field) {
                    if ($image->isDirty($field)) {
                        throw new RuntimeException(
                            "Verified evidence is immutable; cannot modify [{$field}]."
                        );
                    }
                }
            }
        });

        // Hard deletes are reserved for the retention/purge job, which should
        // log a `record.purged` audit entry when it runs.
    }

    public function offence(): BelongsTo
    {
        return $this->belongsTo(Offence::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
