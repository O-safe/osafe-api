<?php

namespace App\Models\User;

use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\Geofence;
use App\Models\Notification\Alert;
use App\Models\Notification\NotificationPreference;
use App\Models\Notification\OsafeNotification;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupLga;
use App\Models\Setup\SetupStatus;
use App\Models\Setup\SetupTitle;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\UserSubscription;
use App\Models\Support\SupportTicket;
use App\Models\System\MfaMethod;
use App\Models\System\SecuritySetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'title_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender_id',
        'email',
        'mobile_number',
        'home_address',
        'lga_id',
        'nin',
        'passport',
        'status_id',
        'password',
        'created_by',
        'updated_by',
        'login_attempt',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- Legacy Setup Relationships ---

    public function title(): BelongsTo
    {
        return $this->belongsTo(SetupTitle::class, 'title_id', 'title_id');
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(SetupGender::class, 'gender_id', 'gender_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(SetupLga::class, 'lga_id', 'lga_id');
    }

    // --- O SAFE Relationships ---

    public function ownedFamilies(): HasMany
    {
        return $this->hasMany(Family::class, 'owner_user_id', 'user_id');
    }

    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'user_id', 'user_id');
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_members', 'user_id', 'family_id', 'user_id', 'family_id')
            ->withPivot(['family_member_id', 'role', 'joined_at', 'status'])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'user_id', 'user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'user_id', 'user_id');
    }

    public function deviceAssignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class, 'user_id', 'user_id');
    }

    public function assignedDevices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_assignments', 'user_id', 'device_id', 'user_id', 'device_id')
            ->withPivot(['assignment_id', 'assigned_by', 'status', 'assigned_at', 'revoked_at', 'notes'])
            ->withTimestamps();
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'user_id', 'user_id');
    }

    public function osafeNotifications(): HasMany
    {
        return $this->hasMany(OsafeNotification::class, 'user_id', 'user_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'user_id', 'user_id');
    }

    public function geofences(): HasMany
    {
        return $this->hasMany(Geofence::class, 'user_id', 'user_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id', 'user_id');
    }

    public function securitySetting(): HasOne
    {
        return $this->hasOne(SecuritySetting::class, 'user_id', 'user_id');
    }

    public function mfaMethods(): HasMany
    {
        return $this->hasMany(MfaMethod::class, 'user_id', 'user_id');
    }

    const DEFAULT_PASSPORT = 'default.png';
}
