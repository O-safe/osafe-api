<?php

namespace App\Models\Family;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Family extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\FamilyFactory::new();
    }

    protected $table = 'families';
    protected $primaryKey = 'family_id';

    protected $fillable = [
        'name',
        'owner_user_id',
        'description',
        'avatar',
        'invite_code',
        'max_members',
        'status_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'max_members' => 'integer',
        'status_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id', 'family_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_members', 'family_id', 'user_id', 'family_id', 'user_id')
            ->withPivot(['family_member_id', 'role', 'joined_at', 'status'])
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FamilyInvitation::class, 'family_id', 'family_id');
    }
}
