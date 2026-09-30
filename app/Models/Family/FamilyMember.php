<?php

namespace App\Models\Family;

use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyMember extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\FamilyMemberFactory::new();
    }

    protected $table = 'family_members';
    protected $primaryKey = 'family_member_id';

    protected $fillable = [
        'family_id',
        'user_id',
        'role',
        'nickname',
        'relationship',
        'joined_at',
        'status_id',
        'added_by',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'status_id' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id', 'family_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FamilyMemberPermission::class, 'family_member_id', 'family_member_id');
    }
}
