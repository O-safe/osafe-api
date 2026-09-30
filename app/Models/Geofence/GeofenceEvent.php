<?php

namespace App\Models\Geofence;

use App\Enums\GeofenceEventType;
use App\Models\Device\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceEvent extends Model
{
    protected $table = 'geofence_events';
    protected $primaryKey = 'event_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'geofence_id',
        'device_id',
        'event_type',
        'latitude',
        'longitude',
        'occurred_at',
    ];

    protected $casts = [
        'event_type' => GeofenceEventType::class,
        'latitude' => 'float',
        'longitude' => 'float',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class, 'geofence_id', 'geofence_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
