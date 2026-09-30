<?php

namespace App\Models\Subscription;

use App\Enums\BillingTransactionStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingTransaction extends Model
{
    protected $table = 'billing_transactions';
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'reference',
        'gateway_reference',
        'gateway',
        'amount',
        'currency',
        'type',
        'status',
        'description',
        'payment_method_id',
        'gateway_response',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'status' => BillingTransactionStatus::class,
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id', 'subscription_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }
}
