<?php

namespace App\Models;

use App\Enums\Severity;
use App\Enums\WatchlistStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WatchlistVehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plate',
        'plate_normalized',
        'vehicle_make',
        'vehicle_color',
        'vehicle_type',
        'reason',
        'severity',
        'instructions',
        'status',
        'created_by',
        'cleared_by',
        'cleared_at',
        'source_offence_id',
    ];

    protected function casts(): array
    {
        return [
            'severity'   => Severity::class,
            'status'     => WatchlistStatus::class,
            'cleared_at' => 'datetime',
        ];
    }

    /**
     * Keep plate_normalized in sync automatically on every save, so matching is
     * always reliable no matter how the plate was typed.
     */
    protected static function booted(): void
    {
        static::saving(function (WatchlistVehicle $vehicle) {
            $vehicle->plate_normalized = static::normalizePlate($vehicle->plate);
        });
    }

    /** Uppercase, strip everything that isn't a letter or digit. */
    public static function normalizePlate(?string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate ?? ''));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clearedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function sourceOffence(): BelongsTo
    {
        return $this->belongsTo(Offence::class, 'source_offence_id');
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }
}
