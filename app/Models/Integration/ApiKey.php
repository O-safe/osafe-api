<?php

namespace App\Models\Integration;

use App\Models\Admin\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'api_key_id';

    protected $fillable = [
        'name',
        'key_hash',
        'secret_prefix',
        'permissions',
        'last_used_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
