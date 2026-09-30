<?php

namespace App\Models\Location;

use App\Models\Device\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationEvent extends Model
{
    protected $table = 'location_events';
    protected $primaryKey = 'event_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'device_id',
        'event_type',
        'latitude',
        'longitude',
        'details',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'details' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
