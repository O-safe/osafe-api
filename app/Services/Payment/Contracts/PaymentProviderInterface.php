<?php

namespace App\Services\Payment\Contracts;

use App\Models\User\User;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    public function getName(): string;

    /**
     * Initialize a payment transaction session.
     */
    public function initializePayment(User $user, float $amount, string $currency, array $metadata = []): array;

    /**
     * Verify payment status with gateway provider.
     */
    public function verifyPayment(string $reference): array;

    /**
     * Verify incoming webhook signature.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse incoming webhook payload into unified event structure.
     */
    public function parseWebhookPayload(array $payload): array;
}
