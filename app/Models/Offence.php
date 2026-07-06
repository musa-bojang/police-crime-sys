<?php

namespace App\Models;

use App\Enums\OffenceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offence extends Model
{
    use HasUuids;      // string UUID primary key
    use SoftDeletes;   // deleted_at is a retention tool, not a user action

    /**
     * The phone generates the UUID, so the key is NOT auto-incrementing
     * and is a string. HasUuids will only fill an id if the client didn't
     * supply one, so offline-created ids pass straight through.
     */
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'reference_number',
        'officer_id',
        'device_id',
        'offence_type',
        'offence_description',
        'vehicle_plate',
        'vehicle_color',
        'vehicle_make',
        'vehicle_type',
        'driver_gender',
        'driver_name',
        'driver_fled',
        'latitude',
        'longitude',
        'location_description',
        'occurred_at',
        'captured_at',
        'synced_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'version',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'driver_fled' => 'boolean',
            'latitude'    => 'decimal:8',
            'longitude'   => 'decimal:8',
            'occurred_at' => 'datetime',
            'captured_at' => 'datetime',
            'synced_at'   => 'datetime',
            'reviewed_at' => 'datetime',
            'version'     => 'integer',
            'status'      => OffenceStatus::class,
            'metadata'    => 'array',
        ];
    }

    // The officer who recorded the offence.
    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    // The supervisor who reviewed it (nullable).
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Evidence photos attached to this offence.
    public function images(): HasMany
    {
        return $this->hasMany(OffenceImage::class);
    }
}
