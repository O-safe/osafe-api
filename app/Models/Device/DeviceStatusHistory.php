<?php

namespace App\Models\Device;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStatusHistory extends Model
{
    protected $table = 'device_status_history';
    protected $primaryKey = 'history_id';

    const CREATED_AT = 'changed_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'device_id',
        'previous_status',
        'new_status',
        'changed_by',
        'changed_by_type',
        'reason',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'new_status' => DeviceStatus::class,
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
