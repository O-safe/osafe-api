<?php

namespace App\Models\Admin;

use App\Models\Integration\ApiKey;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupLga;
use App\Models\Setup\SetupStatus;
use App\Models\Setup\SetupTitle;
use App\Models\Support\SupportTicket;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Staff extends Authenticatable
{
    use HasRoles, HasApiTokens, Notifiable;

    protected $guard_name = 'admin';
    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'staff_id',
        'title_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender_id',
        'email',
        'mobile_number',
        'home_address',
        'date_of_birth',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(SetupGender::class, 'gender_id', 'gender_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(SetupTitle::class, 'title_id', 'title_id');
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(SetupLga::class, 'lga_id', 'lga_id');
    }

    public function createdApiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'created_by', 'staff_id');
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_staff_id', 'staff_id');
    }

    const DEFAULT_PASSPORT = 'default.png';
}
