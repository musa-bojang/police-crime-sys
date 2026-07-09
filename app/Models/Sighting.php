<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sighting extends Model
{
    protected $fillable = [
        'watchlist_vehicle_id',
        'officer_id',
        'plate_checked',
        'latitude',
        'longitude',
        'device_id',
        'sighted_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'   => 'decimal:8',
            'longitude'  => 'decimal:8',
            'sighted_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(WatchlistVehicle::class, 'watchlist_vehicle_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
