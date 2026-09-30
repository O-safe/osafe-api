<?php

namespace App\Models\Device;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceNetwork extends Model
{
    protected $table = 'device_networks';
    protected $primaryKey = 'network_id';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'type',
        'carrier',
        'ssid',
        'signal_strength',
        'signal_dbm',
        'is_roaming',
        'ip_address',
        'recorded_at',
    ];

    protected $casts = [
        'is_roaming' => 'boolean',
        'signal_dbm' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
