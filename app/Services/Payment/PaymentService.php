<?php

namespace App\Services\Payment;

use App\Enums\BillingTransactionStatus;
use App\Models\Integration\WebhookEvent;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected PaymentProviderInterface $provider,
        protected SubscriptionService $subscriptionService,
        protected AuditLogService $auditLogService
    ) {}

    public function initializeCheckout(
        User $user,
        SubscriptionPlan $plan,
        string $billingCycle = 'monthly',
        ?PaymentMethod $paymentMethod = null
    ): array {
        if (!$plan->is_active) {
            throw new InvalidArgumentException("Subscription plan {$plan->name} is not available for purchase.");
        }

        $billingCycle = strtolower($billingCycle);
        $amount = $billingCycle === 'yearly' ? (float) $plan->price_yearly : (float) $plan->price_monthly;
        $currency = $plan->currency ?? 'NGN';
        $reference = 'TXN_' . Str::upper(Str::random(16));

        $transaction = BillingTransaction::create([
            'user_id' => $user->user_id,
            'subscription_id' => null,
            'reference' => $reference,
            'gateway' => $this->provider->getName(),
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'charge',
            'status' => BillingTransactionStatus::Pending,
            'description' => "Subscription payment for {$plan->name} ({$billingCycle})",
            'metadata' => [
                'plan_id' => $plan->plan_id,
                'plan_slug' => $plan->slug,
                'billing_cycle' => $billingCycle,
                'payment_method_id' => $paymentMethod?->payment_method_id,
            ],
        ]);

        $gatewayResponse = $this->provider->initializePayment($user, $amount, $currency, [
            'reference' => $reference,
            'plan_id' => $plan->plan_id,
        ]);

        $this->auditLogService->log(
            $user,
            'payment.initialized',
            BillingTransaction::class,
            (string) $transaction->transaction_id,
            null,
            [
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'plan_id' => $plan->plan_id,
            ]
        );

        return [
            'transaction' => $transaction,
            'checkout_url' => $gatewayResponse['checkout_url'] ?? null,
            'reference' => $reference,
        ];
    }

    public function verifyAndProcessPayment(string $reference): BillingTransaction
    {
        return DB::transaction(function () use ($reference) {
            $transaction = BillingTransaction::where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent check under row lock: if already successful, return transaction directly
            if ($transaction->status === BillingTransactionStatus::Successful) {
                return $transaction;
            }

            $verification = $this->provider->verifyPayment($reference);
            $user = User::findOrFail($transaction->user_id);

            // Amount validation check if verification provides an amount > 0
            $verifiedAmount = (float) ($verification['amount'] ?? 0);
            $isAmountValid = $verifiedAmount <= 0 || $verifiedAmount >= (float) $transaction->amount;

            if (($verification['status'] ?? 'failed') === 'successful' && $isAmountValid) {
                $transaction->update([
                    'status' => BillingTransactionStatus::Successful,
                    'paid_at' => now(),
                    'gateway_reference' => $verification['gateway_reference'] ?? null,
                    'gateway_response' => $verification['raw'] ?? null,
                ]);

                $planId = $transaction->metadata['plan_id'] ?? null;
                $billingCycle = $transaction->metadata['billing_cycle'] ?? 'monthly';
                $plan = $planId ? SubscriptionPlan::find($planId) : null;

                if ($plan) {
                    $existingSub = $this->subscriptionService->getActiveSubscription($user);
                    if ($existingSub && $existingSub->plan_id === $plan->plan_id) {
                        $this->subscriptionService->renewSubscription($existingSub, $transaction);
                    } else {
                        $this->subscriptionService->activateSubscription($user, $plan, $billingCycle, $transaction);
                    }
                }

                $this->auditLogService->log(
                    $user,
                    'payment.successful',
                    BillingTransaction::class,
                    (string) $transaction->transaction_id,
                    null,
                    [
                        'reference' => $transaction->reference,
                        'amount' => $transaction->amount,
                    ]
                );
            } else {
                $transaction->update([
                    'status' => BillingTransactionStatus::Failed,
                    'gateway_response' => $verification['raw'] ?? null,
                ]);

                $activeSub = $this->subscriptionService->getActiveSubscription($user);
                if ($activeSub) {
                    $this->subscriptionService->markPastDue($activeSub);
                }

                $this->auditLogService->log(
                    $user,
                    'payment.failed',
                    BillingTransaction::class,
                    (string) $transaction->transaction_id,
                    null,
                    ['reference' => $transaction->reference]
                );
            }

            return $transaction->fresh();
        });
    }

    public function handleWebhook(Request $request): WebhookEvent
    {
        $signatureVerified = $this->provider->verifyWebhookSignature($request);
        if (!$signatureVerified) {
            throw new AuthorizationException('Invalid webhook signature');
        }

        $payload = $request->all();
        $eventId = $request->header('X-Webhook-Idempotency')
            ?? $payload['event_id']
            ?? $payload['id']
            ?? ($payload['data']['reference'] ?? null)
            ?? 'WH_' . md5(json_encode($payload));

        return DB::transaction(function () use ($request, $payload, $eventId) {
            // Idempotency check with lock: if event was already processed, return existing record
            $existingEvent = WebhookEvent::where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($existingEvent && $existingEvent->status === 'processed') {
                return $existingEvent;
            }

            $event = WebhookEvent::updateOrCreate(
                ['event_id' => $eventId],
                [
                    'source' => $this->provider->getName(),
                    'event_type' => $payload['event'] ?? $payload['event_type'] ?? 'payment.event',
                    'payload' => $payload,
                    'status' => 'processing',
                    'attempts' => ($existingEvent?->attempts ?? 0) + 1,
                    'ip_address' => $request->ip(),
                    'signature' => $request->header('X-Webhook-Signature'),
                    'signature_verified' => true,
                ]
            );

            $parsed = $this->provider->parseWebhookPayload($payload);
            $reference = $parsed['reference'] ?? null;

            if ($reference && BillingTransaction::where('reference', $reference)->exists()) {
                $this->verifyAndProcessPayment($reference);
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $this->auditLogService->log(
                null,
                'webhook.processed',
                WebhookEvent::class,
                (string) $event->webhook_event_id,
                null,
                [
                    'event_id' => $eventId,
                    'event_type' => $event->event_type,
                ]
            );

            return $event;
        });
    }
}
