<?php

namespace App\Models\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSubscription extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\UserSubscriptionFactory::new();
    }

    protected $table = 'user_subscriptions';
    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'auto_renew',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'subscription_id', 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::Expired)
            ->orWhere(function ($q) {
                $q->whereNotNull('ends_at')->where('ends_at', '<=', now());
            });
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', SubscriptionStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopePastDue($query)
    {
        return $query->where('status', SubscriptionStatus::PastDue);
    }
}
