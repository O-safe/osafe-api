<?php

namespace App\Models\System;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaMethod extends Model
{
    protected $table = 'mfa_methods';
    protected $primaryKey = 'mfa_method_id';

    protected $fillable = [
        'user_id',
        'type',
        'secret',
        'backup_codes',
        'is_primary',
        'is_verified',
        'verified_at',
        'last_used_at',
    ];

    protected $hidden = [
        'secret',
        'backup_codes',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'backup_codes' => 'array',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
