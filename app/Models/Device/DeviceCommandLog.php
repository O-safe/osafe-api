<?php

namespace App\Models\Device;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommandLog extends Model
{
    protected $table = 'device_command_logs';
    protected $primaryKey = 'log_id';

    const CREATED_AT = 'logged_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'command_id',
        'device_id',
        'event',
        'message',
        'response_payload',
        'ip_address',
        'logged_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'logged_at' => 'datetime',
    ];

    public function command(): BelongsTo
    {
        return $this->belongsTo(DeviceCommand::class, 'command_id', 'command_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
