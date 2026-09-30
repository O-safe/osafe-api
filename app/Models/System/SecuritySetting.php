<?php

namespace App\Models\System;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecuritySetting extends Model
{
    protected $table = 'security_settings';
    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'user_id',
        'mfa_enabled',
        'mfa_required',
        'failed_login_attempts',
        'locked_until',
        'last_password_change',
    ];

    protected $hidden = [
        'failed_login_attempts',
    ];

    protected $casts = [
        'mfa_enabled' => 'boolean',
        'mfa_required' => 'boolean',
        'failed_login_attempts' => 'integer',
        'locked_until' => 'datetime',
        'last_password_change' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
