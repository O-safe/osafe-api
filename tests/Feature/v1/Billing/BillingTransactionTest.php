<?php

namespace Tests\Feature\v1\Billing;

use App\Enums\BillingTransactionStatus;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->paymentService = app(PaymentService::class);
    }

    public function test_transaction_initialized_with_pending_status(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price_monthly' => 2500.00, 'currency' => 'NGN']);

        $result = $this->paymentService->initializeCheckout($user, $plan, 'monthly');

        /** @var BillingTransaction $transaction */
        $transaction = $result['transaction'];

        $this->assertEquals(BillingTransactionStatus::Pending, $transaction->status);
        $this->assertEquals(2500.00, $transaction->amount);
        $this->assertEquals('NGN', $transaction->currency);
    }

    public function test_transaction_verification_updates_status(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $result = $this->paymentService->initializeCheckout($user, $plan);
        $ref = $result['reference'];

        $processed = $this->paymentService->verifyAndProcessPayment($ref);

        $this->assertEquals(BillingTransactionStatus::Successful, $processed->status);
        $this->assertNotNull($processed->paid_at);
    }

    public function test_transaction_failure_handling(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Reference containing 'FAIL' simulates gateway failure in NullPaymentProvider
        $reference = 'TXN_FAIL_' . uniqid();
        $transaction = BillingTransaction::create([
            'user_id' => $user->user_id,
            'reference' => $reference,
            'amount' => 5000.00,
            'currency' => 'NGN',
            'type' => 'charge',
            'status' => BillingTransactionStatus::Pending,
            'metadata' => ['plan_id' => $plan->plan_id],
        ]);

        $processed = $this->paymentService->verifyAndProcessPayment($reference);

        $this->assertEquals(BillingTransactionStatus::Failed, $processed->status);
        $this->assertNull($processed->paid_at);
    }
}
