<?php

namespace Tests\Feature\v1\Billing;

use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\System\AuditLog;
use App\Models\User\User;
use App\Services\Payment\PaymentService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAuditTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $subscriptionService;
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->subscriptionService = app(SubscriptionService::class);
        $this->paymentService = app(PaymentService::class);
    }

    public function test_subscription_and_payment_operations_produce_audit_logs(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // 1. Checkout
        $res = $this->paymentService->initializeCheckout($user, $plan);
        $ref = $res['reference'];

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.initialized',
        ]);

        // 2. Verification
        $this->paymentService->verifyAndProcessPayment($ref);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.successful',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.activated',
        ]);

        // 3. Cancellation
        $activeSub = $this->subscriptionService->getActiveSubscription($user);
        $this->subscriptionService->cancelSubscription($user, $activeSub);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.cancelled',
        ]);
    }

    public function test_audit_logs_contain_no_sensitive_secrets(): void
    {
        $admin = Staff::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();
        $devAdmin = 'DEV_AUDIT_SEC_' . uniqid();
        UserDevice::create(['user_id' => $admin->staff_id, 'device_id' => $devAdmin, 'device_type' => 'Test', 'verified_at' => now()]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $res = $this->paymentService->initializeCheckout($user, $plan);

        $logs = AuditLog::all();
        foreach ($logs as $log) {
            $json = json_encode($log->toArray());
            $this->assertStringNotContainsString('gateway_token', $json);
            $this->assertStringNotContainsString('secret_key', $json);
            $this->assertStringNotContainsString('password', $json);
        }

        $response = $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $devAdmin])
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(200);
    }
}
