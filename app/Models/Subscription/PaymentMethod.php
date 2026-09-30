<?php

namespace App\Models\Subscription;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'user_id',
        'type',
        'gateway',
        'gateway_token',
        'last_four',
        'brand',
        'bank_name',
        'exp_month',
        'exp_year',
        'is_default',
        'is_active',
        'country_code',
    ];

    protected $hidden = [
        'gateway_token',
    ];

    protected $casts = [
        'gateway_token' => 'encrypted',
        'exp_month' => 'integer',
        'exp_year' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'payment_method_id', 'payment_method_id');
    }
}
