<?php

namespace App\Models\Family;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMemberPermission extends Model
{
    protected $table = 'family_member_permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'family_member_id',
        'permission_key',
        'granted',
        'granted_by',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id', 'family_member_id');
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');
    }
}
