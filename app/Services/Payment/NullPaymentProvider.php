<?php

namespace App\Services\Payment;

use App\Models\User\User;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NullPaymentProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'null_provider';
    }

    public function initializePayment(User $user, float $amount, string $currency, array $metadata = []): array
    {
        $reference = $metadata['reference'] ?? 'TXN_' . Str::upper(Str::random(12));

        return [
            'reference' => $reference,
            'checkout_url' => 'https://checkout.osafe.test/pay/' . $reference,
            'gateway' => $this->getName(),
            'raw' => [
                'status' => 'initialized',
                'user_id' => $user->user_id,
                'amount' => $amount,
                'currency' => $currency,
            ],
        ];
    }

    public function verifyPayment(string $reference): array
    {
        if (str_contains($reference, 'FAIL')) {
            return [
                'status' => 'failed',
                'gateway_reference' => 'GW_FAIL_' . $reference,
                'amount' => 0,
                'currency' => 'NGN',
                'raw' => ['message' => 'Simulated gateway payment failure'],
            ];
        }

        return [
            'status' => 'successful',
            'gateway_reference' => 'GW_SUCCESS_' . $reference,
            'amount' => 0,
            'currency' => 'NGN',
            'raw' => ['message' => 'Simulated gateway payment success'],
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        if ($request->header('X-Webhook-Signature') === 'invalid') {
            return false;
        }

        return true;
    }

    public function parseWebhookPayload(array $payload): array
    {
        $eventType = $payload['event'] ?? $payload['event_type'] ?? 'payment.success';
        $data = $payload['data'] ?? $payload;

        $reference = $data['reference'] ?? $payload['reference'] ?? 'TXN_UNKNOWN';
        $gatewayRef = $data['gateway_reference'] ?? $data['id'] ?? $payload['gateway_reference'] ?? 'GW_WH_' . Str::random(8);
        $status = $data['status'] ?? $payload['status'] ?? ($eventType === 'payment.failed' ? 'failed' : 'successful');
        $amount = (float) ($data['amount'] ?? $payload['amount'] ?? 0);
        $currency = $data['currency'] ?? $payload['currency'] ?? 'NGN';

        return [
            'event_type' => $eventType,
            'reference' => $reference,
            'gateway_reference' => (string) $gatewayRef,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
            'raw' => $payload,
        ];
    }
}
