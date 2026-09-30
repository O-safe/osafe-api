<?php

namespace App\Models\Geofence;

use App\Models\Device\Device;
use App\Models\Family\Family;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Geofence extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\GeofenceFactory::new();
    }

    protected $table = 'geofences';
    protected $primaryKey = 'geofence_id';

    protected $fillable = [
        'owner_user_id',
        'family_id',
        'name',
        'description',
        'center_latitude',
        'center_longitude',
        'radius_meters',
        'shape',
        'alert_on_entry',
        'alert_on_exit',
        'is_active',
        'color',
        'active_from',
        'active_until',
        'active_days',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'center_latitude' => 'float',
        'center_longitude' => 'float',
        'radius_meters' => 'integer',
        'alert_on_entry' => 'boolean',
        'alert_on_exit' => 'boolean',
        'is_active' => 'boolean',
        'active_days' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id', 'family_id');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'geofence_device', 'geofence_id', 'device_id')
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(GeofenceEvent::class, 'geofence_id', 'geofence_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
