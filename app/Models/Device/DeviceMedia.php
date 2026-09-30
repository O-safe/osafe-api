<?php

namespace App\Models\Device;

use App\Models\Notification\Alert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMedia extends Model
{
    protected $table = 'device_media';
    protected $primaryKey = 'media_id';

    protected $fillable = [
        'device_id',
        'alert_id',
        'command_id',
        'media_type',
        'file_path',
        'file_size',
        'mime_type',
        'captured_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'captured_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id', 'alert_id');
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(DeviceCommand::class, 'command_id', 'command_id');
    }
}
