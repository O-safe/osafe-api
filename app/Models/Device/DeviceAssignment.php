<?php

namespace App\Models\Device;

use App\Enums\DeviceAssignmentStatus;
use App\Models\Family\Family;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAssignment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\DeviceAssignmentFactory::new();
    }

    protected $table = 'device_assignments';
    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'device_id',
        'user_id',
        'family_id',
        'assigned_by',
        'assigned_by_type',
        'status',
        'assigned_at',
        'revoked_at',
        'revocation_reason',
        'metadata',
    ];

    protected $casts = [
        'status' => DeviceAssignmentStatus::class,
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id', 'family_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', DeviceAssignmentStatus::Active);
    }
}
