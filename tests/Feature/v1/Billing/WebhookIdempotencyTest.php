<?php

namespace Tests\Feature\v1\Billing;

use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_duplicate_webhook_delivery_is_idempotent(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $ref = 'TXN_IDEMP_' . uniqid();
        $eventId = 'WH_EVENT_REPEAT_123';

        BillingTransaction::create([
            'user_id' => $user->user_id,
            'reference' => $ref,
            'amount' => 5000.00,
            'currency' => 'NGN',
            'type' => 'charge',
            'status' => 'pending',
            'metadata' => ['plan_id' => $plan->plan_id],
        ]);

        $payload = [
            'event' => 'payment.success',
            'data' => [
                'reference' => $ref,
                'status' => 'successful',
            ],
        ];

        // First Delivery
        $res1 = $this->withHeaders([
            'X-Webhook-Signature' => 'valid_sig',
            'X-Webhook-Idempotency' => $eventId,
        ])->postJson('/api/v1/webhooks/billing', $payload);

        $res1->assertStatus(200);

        $initialSubCount = \App\Models\Subscription\UserSubscription::where('user_id', $user->user_id)->count();

        // Second Delivery (Duplicate)
        $res2 = $this->withHeaders([
            'X-Webhook-Signature' => 'valid_sig',
            'X-Webhook-Idempotency' => $eventId,
        ])->postJson('/api/v1/webhooks/billing', $payload);

        $res2->assertStatus(200);

        // Confirm subscription count did NOT increase
        $finalSubCount = \App\Models\Subscription\UserSubscription::where('user_id', $user->user_id)->count();
        $this->assertEquals($initialSubCount, $finalSubCount);

        // Confirm only 1 webhook event record created
        $this->assertDatabaseCount('webhook_events', 1);
    }
}
