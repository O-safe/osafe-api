<?php

namespace App\Models\Integration;

use App\Models\Device\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceIntegration extends Model
{
    protected $table = 'device_integrations';
    protected $primaryKey = 'integration_id';

    protected $fillable = [
        'device_id',
        'user_id',
        'platform',
        'external_device_id',
        'external_account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'config',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'config' => 'array',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
