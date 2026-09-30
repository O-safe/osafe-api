<?php

namespace App\Models\Device;

use App\Enums\DeviceStatus;
use App\Enums\DeviceAssignmentStatus;
use App\Models\Geofence\Geofence;
use App\Models\Geofence\GeofenceEvent;
use App\Models\Integration\DeviceIntegration;
use App\Models\Location\DeviceLocation;
use App\Models\Location\LocationEvent;
use App\Models\Notification\Alert;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\DeviceFactory::new();
    }

    protected $table = 'devices';
    protected $primaryKey = 'device_id';

    protected $fillable = [
        'serial_number',
        'imei',
        'mac_address',
        'model',
        'manufacturer',
        'hardware_version',
        'firmware_version',
        'os_type',
        'os_version',
        'platform',
        'name',
        'color',
        'connectivity',
        'status',
        'is_activated',
        'activated_at',
        'battery_level',
        'battery_status',
        'is_online',
        'last_seen_at',
        'last_ip_address',
        'push_token',
        'metadata',
        'registered_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => DeviceStatus::class,
        'is_activated' => 'boolean',
        'is_online' => 'boolean',
        'battery_level' => 'float',
        'activated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class, 'device_id', 'device_id');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(DeviceAssignment::class, 'device_id', 'device_id')
            ->where('status', DeviceAssignmentStatus::Active->value);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'device_assignments', 'device_id', 'user_id', 'device_id', 'user_id')
            ->withPivot(['assignment_id', 'assigned_by', 'status', 'assigned_at', 'revoked_at', 'notes'])
            ->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(DeviceStatusHistory::class, 'device_id', 'device_id');
    }

    public function networkHistory(): HasMany
    {
        return $this->hasMany(DeviceNetwork::class, 'device_id', 'device_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DeviceLocation::class, 'device_id', 'device_id');
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(DeviceLocation::class, 'device_id', 'device_id')
            ->latestOfMany('recorded_at');
    }

    public function locationEvents(): HasMany
    {
        return $this->hasMany(LocationEvent::class, 'device_id', 'device_id');
    }

    public function geofences(): BelongsToMany
    {
        return $this->belongsToMany(Geofence::class, 'geofence_device', 'device_id', 'geofence_id')
            ->withTimestamps();
    }

    public function geofenceEvents(): HasMany
    {
        return $this->hasMany(GeofenceEvent::class, 'device_id', 'device_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'device_id', 'device_id');
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class, 'device_id', 'device_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DeviceMedia::class, 'device_id', 'device_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(DeviceIntegration::class, 'device_id', 'device_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'device_id', 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', DeviceStatus::Active);
    }

    public function scopeAssigned($query)
    {
        return $query->whereHas('assignments', fn ($q) => $q->where('status', DeviceAssignmentStatus::Active));
    }

    public function scopeUnassigned($query)
    {
        return $query->whereDoesntHave('assignments', fn ($q) => $q->where('status', DeviceAssignmentStatus::Active));
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }
}
