<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_method_id' => $this->payment_method_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'gateway' => $this->gateway,
            'last_four' => $this->last_four,
            'brand' => $this->brand,
            'bank_name' => $this->bank_name,
            'exp_month' => $this->exp_month,
            'exp_year' => $this->exp_year,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
