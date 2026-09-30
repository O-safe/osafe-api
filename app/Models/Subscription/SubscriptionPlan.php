<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\SubscriptionPlanFactory::new();
    }

    protected $table = 'subscription_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_devices',
        'max_family_members',
        'location_history_days',
        'price_monthly',
        'price_yearly',
        'currency',
        'is_active',
        'is_featured',
        'features',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_devices' => 'integer',
        'max_family_members' => 'integer',
        'location_history_days' => 'integer',
        'sort_order' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id', 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
