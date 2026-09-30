<?php

namespace Tests\Feature\v1\Billing;

use App\Enums\BillingTransactionStatus;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $response = $this->withHeaders([
            'X-Webhook-Signature' => 'invalid',
        ])->postJson('/api/v1/webhooks/billing', [
            'event' => 'payment.success',
            'data' => ['reference' => 'TXN_TEST_FAKE'],
        ]);

        $response->assertStatus(403);
    }

    public function test_valid_webhook_processes_pending_transaction(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $ref = 'TXN_WH_VALID_' . uniqid();

        BillingTransaction::create([
            'user_id' => $user->user_id,
            'reference' => $ref,
            'amount' => 4500.00,
            'currency' => 'NGN',
            'type' => 'charge',
            'status' => BillingTransactionStatus::Pending,
            'metadata' => ['plan_id' => $plan->plan_id],
        ]);

        $response = $this->withHeaders([
            'X-Webhook-Signature' => 'valid_secret',
            'X-Webhook-Idempotency' => 'WH_IDEMP_' . uniqid(),
        ])->postJson('/api/v1/webhooks/billing', [
            'event' => 'payment.success',
            'data' => [
                'reference' => $ref,
                'status' => 'successful',
                'amount' => 4500.00,
                'currency' => 'NGN',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('billing_transactions', [
            'reference' => $ref,
            'status' => BillingTransactionStatus::Successful->value,
        ]);
    }
}
