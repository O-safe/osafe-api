<?php

namespace App\Models\Device;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceCommand extends Model
{
    protected $table = 'device_commands';
    protected $primaryKey = 'command_id';

    protected $fillable = [
        'device_id',
        'issued_by_type',
        'issued_by',
        'command_type',
        'status',
        'payload',
        'response_data',
        'executed_at',
    ];

    protected $casts = [
        'command_type' => DeviceCommandType::class,
        'status' => DeviceCommandStatus::class,
        'payload' => 'array',
        'response_data' => 'array',
        'executed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    public function issuedBy(): MorphTo
    {
        return $this->morphTo('issuedBy', 'issued_by_type', 'issued_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeviceCommandLog::class, 'command_id', 'command_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DeviceMedia::class, 'command_id', 'command_id');
    }
}
