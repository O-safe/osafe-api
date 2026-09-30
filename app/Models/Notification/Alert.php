<?php

namespace App\Models\Notification;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Device\Device;
use App\Models\Device\DeviceMedia;
use App\Models\Geofence\Geofence;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\AlertFactory::new();
    }

    protected $table = 'alerts';
    protected $primaryKey = 'alert_id';

    protected $fillable = [
        'user_id',
        'device_id',
        'geofence_id',
        'location_event_id',
        'type',
        'severity',
        'title',
        'body',
        'status',
        'is_read',
        'is_resolved',
        'resolved_by',
        'read_at',
        'resolved_at',
        'metadata',
        'triggered_at',
    ];

    protected $casts = [
        'severity' => AlertSeverity::class,
        'status' => AlertStatus::class,
        'is_read' => 'boolean',
        'is_resolved' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
        'triggered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class, 'geofence_id', 'geofence_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(OsafeNotification::class, 'alert_id', 'alert_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DeviceMedia::class, 'alert_id', 'alert_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverity::Critical);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
}
