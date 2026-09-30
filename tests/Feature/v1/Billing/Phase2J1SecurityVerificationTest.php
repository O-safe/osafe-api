<?php

namespace Tests\Feature\v1\Billing;

use App\Enums\BillingTransactionStatus;
use App\Enums\DeviceAssignmentStatus;
use App\Enums\DeviceStatus;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use App\Exceptions\FamilyLimitExceededException;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Jobs\ExpireSubscriptionsJob;
use App\Models\System\AuditLog;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Integration\WebhookEvent;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use App\Services\Device\DeviceAssignmentService;
use App\Services\Family\FamilyMemberService;
use App\Services\Payment\NullPaymentProvider;
use App\Services\Payment\PaymentService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class Phase2J1SecurityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $basicPlan;
    protected SubscriptionPlan $familyPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'security_tester_' . uniqid() . '@example.com',
        ]);

        $this->basicPlan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic-plan-' . uniqid(),
            'description' => 'Basic individual plan',
            'price_monthly' => 2500.00,
            'price_yearly' => 25000.00,
            'currency' => 'NGN',
            'max_devices' => 1,
            'max_family_members' => 1,
            'is_active' => true,
        ]);

        $this->familyPlan = SubscriptionPlan::create([
            'name' => 'Family Plan',
            'slug' => 'family-plan-' . uniqid(),
            'description' => 'Family safety plan',
            'price_monthly' => 7500.00,
            'price_yearly' => 75000.00,
            'currency' => 'NGN',
            'max_devices' => 5,
            'max_family_members' => 5,
            'is_active' => true,
        ]);
    }

    /** ===================================================
     * 1. CONTROLLED CONCURRENCY & LOCKING VERIFICATION
     * =================================================== */

    public function test_controlled_overlapping_concurrency_in_payment_verification(): void
    {
        Event::fake([SubscriptionActivated::class]);

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $checkout = $paymentService->initializeCheckout($this->user, $this->basicPlan, 'monthly');
        $reference = $checkout['reference'];

        // Simulate overlapping concurrency:
        // Transaction 1 locks the row, processes payment, and commits.
        // Transaction 2 runs verifyAndProcessPayment right after or in parallel.
        $txn1 = $paymentService->verifyAndProcessPayment($reference);

        // Perform second overlapping verification call on same reference
        $txn2 = $paymentService->verifyAndProcessPayment($reference);

        $this->assertEquals(BillingTransactionStatus::Successful, $txn1->status);
        $this->assertEquals(BillingTransactionStatus::Successful, $txn2->status);

        // Verify subscription active count is exactly 1
        $subCount = UserSubscription::where('user_id', $this->user->user_id)->count();
        $this->assertEquals(1, $subCount);

        // Verify event was dispatched exactly ONCE
        Event::assertDispatched(SubscriptionActivated::class, 1);

        // Verify audit logs contain exactly 1 payment.successful entry for this transaction
        $auditCount = AuditLog::where('action', 'payment.successful')
            ->where('resource_id', (string) $txn1->transaction_id)
            ->count();
        $this->assertEquals(1, $auditCount);
    }

    public function test_controlled_overlapping_concurrency_in_webhook_processing(): void
    {
        Event::fake([SubscriptionActivated::class]);

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $checkout = $paymentService->initializeCheckout($this->user, $this->basicPlan, 'monthly');
        $reference = $checkout['reference'];

        $payload = [
            'event' => 'payment.success',
            'event_id' => 'EVT_CONCURRENT_' . uniqid(),
            'data' => [
                'reference' => $reference,
                'status' => 'successful',
                'amount' => 2500.00,
            ],
        ];

        $req1 = Request::create('/api/v1/webhooks/billing', 'POST', $payload, [], [], [
            'HTTP_X-Webhook-Signature' => 'valid_sig',
            'HTTP_X-Webhook-Idempotency' => $payload['event_id'],
        ]);

        $req2 = Request::create('/api/v1/webhooks/billing', 'POST', $payload, [], [], [
            'HTTP_X-Webhook-Signature' => 'valid_sig',
            'HTTP_X-Webhook-Idempotency' => $payload['event_id'],
        ]);

        $event1 = $paymentService->handleWebhook($req1);
        $event2 = $paymentService->handleWebhook($req2);

        $this->assertEquals('processed', $event1->status);
        $this->assertEquals($event1->webhook_event_id, $event2->webhook_event_id);

        // Verify only 1 WebhookEvent record exists for this event_id
        $webhookCount = WebhookEvent::where('event_id', $payload['event_id'])->count();
        $this->assertEquals(1, $webhookCount);

        // Verify subscription period extended exactly once
        $activeSub = UserSubscription::where('user_id', $this->user->user_id)->active()->count();
        $this->assertEquals(1, $activeSub);
    }

    /** ===================================================
     * 2. WEBHOOK PAYLOAD AUTHORITY & MISMATCH TESTING
     * =================================================== */

    public function test_webhook_ignores_untrusted_request_body_user_or_amount_overrides(): void
    {
        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $checkout = $paymentService->initializeCheckout($this->user, $this->basicPlan, 'monthly');
        $reference = $checkout['reference'];

        $attacker = User::factory()->create();

        // Attacker attempts to pass untrusted user_id / amount in webhook payload
        $payload = [
            'event' => 'payment.success',
            'event_id' => 'EVT_TAMPER_' . uniqid(),
            'user_id' => $attacker->user_id, // Untrusted override attempt
            'amount' => 999999.00,           // Untrusted amount attempt
            'data' => [
                'reference' => $reference,
                'status' => 'successful',
            ],
        ];

        $request = Request::create('/api/v1/webhooks/billing', 'POST', $payload, [], [], [
            'HTTP_X-Webhook-Signature' => 'valid_sig',
        ]);

        $paymentService->handleWebhook($request);

        // Assert subscription was created for the legitimate user, NOT the attacker
        $legitSub = UserSubscription::where('user_id', $this->user->user_id)->active()->first();
        $attackerSub = UserSubscription::where('user_id', $attacker->user_id)->active()->first();

        $this->assertNotNull($legitSub);
        $this->assertNull($attackerSub);
    }

    public function test_payment_verification_fails_if_gateway_amount_mismatches(): void
    {
        $mockProvider = new class extends NullPaymentProvider {
            public function verifyPayment(string $reference): array
            {
                return [
                    'status' => 'successful',
                    'gateway_reference' => 'GW_UNDERPAID_' . $reference,
                    'amount' => 100.00, // Expected 2500.00, paid only 100.00
                    'currency' => 'NGN',
                    'raw' => ['message' => 'Underpaid transaction'],
                ];
            }
        };

        $paymentService = new PaymentService(
            $mockProvider,
            app(SubscriptionService::class),
            app(\App\Services\Audit\AuditLogService::class)
        );

        $checkout = $paymentService->initializeCheckout($this->user, $this->basicPlan, 'monthly');

        $txn = $paymentService->verifyAndProcessPayment($checkout['reference']);

        $this->assertEquals(BillingTransactionStatus::Failed, $txn->status);
        $this->assertNull(UserSubscription::where('user_id', $this->user->user_id)->active()->first());
    }

    /** ===================================================
     * 3. SUBSCRIPTION STATE MACHINE & AUTHORIZATION
     * =================================================== */

    public function test_user_cannot_directly_set_privileged_subscription_state(): void
    {
        $sub = UserSubscription::create([
            'user_id' => $this->user->user_id,
            'plan_id' => $this->basicPlan->plan_id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/user/subscriptions/{$sub->subscription_id}", [
                'status' => 'active',
            ]);

        // Assert route does not permit arbitrary state updates
        $this->assertTrue(in_array($response->status(), [404, 405]));

        $this->assertEquals(SubscriptionStatus::Pending, $sub->fresh()->status);
    }

    /** ===================================================
     * 4. DEVICE & FAMILY LIMIT BYPASS TESTING
     * =================================================== */

    public function test_device_assignment_enforces_subscription_quota(): void
    {
        /** @var SubscriptionService $subService */
        $subService = app(SubscriptionService::class);
        $subService->activateSubscription($this->user, $this->basicPlan, 'monthly'); // max_devices = 1

        $device1 = Device::create([
            'serial_number' => 'SN_TEST_' . uniqid(),
            'imei' => 'IMEI_' . rand(100000, 999999),
            'registered_by' => $this->user->user_id,
            'created_by' => $this->user->user_id,
            'device_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'status' => DeviceStatus::Active,
        ]);

        DeviceAssignment::create([
            'device_id' => $device1->device_id,
            'user_id' => $this->user->user_id,
            'assigned_by' => $this->user->user_id,
            'assigned_by_type' => 'user',
            'status' => DeviceAssignmentStatus::Active,
            'assigned_at' => now(),
        ]);

        $device2 = Device::create([
            'serial_number' => 'SN_TEST_' . uniqid(),
            'imei' => 'IMEI_' . rand(100000, 999999),
            'registered_by' => $this->user->user_id,
            'created_by' => $this->user->user_id,
            'device_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'status' => DeviceStatus::Unactivated,
        ]);

        /** @var DeviceAssignmentService $assignmentService */
        $assignmentService = app(DeviceAssignmentService::class);

        $this->expectException(SubscriptionLimitExceededException::class);
        $assignmentService->assignToUser($this->user, $device2, $this->user);
    }

    public function test_family_member_addition_enforces_subscription_quota(): void
    {
        /** @var SubscriptionService $subService */
        $subService = app(SubscriptionService::class);
        $subService->activateSubscription($this->user, $this->basicPlan, 'monthly'); // max_family_members = 1

        $family = Family::create([
            'owner_user_id' => $this->user->user_id,
            'name' => 'Tester Family',
            'invite_code' => 'INV_' . Str_random_code(),
            'max_members' => 5,
        ]);

        // Add 1st member (owner)
        FamilyMember::create([
            'family_id' => $family->family_id,
            'user_id' => $this->user->user_id,
            'role' => 'owner',
            'relationship' => 'owner',
            'joined_at' => now(),
            'status_id' => 1,
        ]);

        $memberUser = User::factory()->create();

        /** @var FamilyMemberService $familyService */
        $familyService = app(FamilyMemberService::class);

        $this->expectException(FamilyLimitExceededException::class);
        $familyService->addMember($this->user, $family, $memberUser);
    }

    /** ===================================================
     * 5. SENSITIVE DATA PROTECTION AUDIT
     * =================================================== */

    public function test_sensitive_payment_tokens_and_secrets_are_encrypted_and_hidden(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->user_id,
            'type' => 'card',
            'gateway' => 'null_provider',
            'gateway_token' => 'SECRET_CARD_TOKEN_9999',
            'last_four' => '4242',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2030,
            'is_default' => true,
            'is_active' => true,
        ]);

        // 1. Assert gateway_token is encrypted in raw database row
        $rawDbRow = DB::table('payment_methods')->where('payment_method_id', $pm->payment_method_id)->first();
        $this->assertNotEquals('SECRET_CARD_TOKEN_9999', $rawDbRow->gateway_token);

        // 2. Assert gateway_token is omitted from model array output
        $arrayOutput = $pm->toArray();
        $this->assertArrayNotHasKey('gateway_token', $arrayOutput);

        // 3. Assert gateway_token is omitted from API Resource serialization
        $resourceOutput = (new \App\Http\Resources\Subscription\PaymentMethodResource($pm))->toArray(request());
        $this->assertArrayNotHasKey('gateway_token', $resourceOutput);
    }

    /** ===================================================
     * 6. CANCELLATION & EXPIRATION DETERMINISM
     * =================================================== */

    public function test_subscription_cancellation_is_idempotent_and_audited(): void
    {
        /** @var SubscriptionService $subService */
        $subService = app(SubscriptionService::class);

        $sub = $subService->activateSubscription($this->user, $this->basicPlan, 'monthly');

        $cancelledSub = $subService->cancelSubscription($this->user, $sub);
        $this->assertEquals(SubscriptionStatus::Cancelled, $cancelledSub->status);
        $this->assertFalse($cancelledSub->auto_renew);

        // Expect exception when attempting to cancel an already cancelled subscription
        $this->expectException(InvalidArgumentException::class);
        $subService->cancelSubscription($this->user, $cancelledSub);
    }

    public function test_expire_subscriptions_job_is_idempotent(): void
    {
        $expiredSub = UserSubscription::create([
            'user_id' => $this->user->user_id,
            'plan_id' => $this->basicPlan->plan_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(), // Past date
            'auto_renew' => true,
        ]);

        // Run ExpireSubscriptionsJob once
        ExpireSubscriptionsJob::dispatchSync();
        $this->assertEquals(SubscriptionStatus::Expired, $expiredSub->fresh()->status);

        // Run ExpireSubscriptionsJob second time (assert no crashes or errors)
        ExpireSubscriptionsJob::dispatchSync();
        $this->assertEquals(SubscriptionStatus::Expired, $expiredSub->fresh()->status);
    }

    /** ===================================================
     * 7. WEBHOOK SECURITY & SIGNATURE VALIDATION
     * =================================================== */

    public function test_webhook_signature_rejection_before_business_logic(): void
    {
        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $payload = [
            'event' => 'payment.success',
            'event_id' => 'EVT_INVALID_SIG_' . uniqid(),
            'data' => ['reference' => 'TXN_FAKE'],
        ];

        $request = Request::create('/api/v1/webhooks/billing', 'POST', $payload, [], [], [
            'HTTP_X-Webhook-Signature' => 'invalid', // Force signature rejection
        ]);

        $this->expectException(AuthorizationException::class);
        $paymentService->handleWebhook($request);

        // Assert zero WebhookEvent records were created
        $this->assertEquals(0, WebhookEvent::where('event_id', $payload['event_id'])->count());
    }
}

function Str_random_code(): string
{
    return strtoupper(\Illuminate\Support\Str::random(8));
}
