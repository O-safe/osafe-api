<?php

namespace Tests\Feature\Security;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceAssignmentStatus;
use App\Enums\DeviceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Integration\DeviceIntegration;
use App\Models\Notification\Alert;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\System\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase2KProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected Staff $admin;
    protected Staff $unprivilegedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->userA = User::factory()->create([
            'email' => 'user_a_' . uniqid() . '@example.com',
            'status_id' => 1,
        ]);

        $this->userB = User::factory()->create([
            'email' => 'user_b_' . uniqid() . '@example.com',
            'status_id' => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $this->admin = Staff::where('email', 'admin@osafe.test')->first();
        if (!$this->admin) {
            $this->admin = Staff::create([
                'staff_id' => 'STF' . date('YmdHis') . rand(100, 999),
                'title_id' => 1,
                'gender_id' => 1,
                'lga_id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin_' . uniqid() . '@example.com',
                'password' => 'password123',
                'status_id' => 1,
                'mobile_number' => '080' . rand(10000000, 99999999),
                'created_by' => 'system',
                'updated_by' => 'system',
            ]);
        }
        if (!$this->admin->hasRole('Super Admin')) {
            $this->admin->assignRole($role);
        }

        $staffRole = Role::firstOrCreate(['name' => 'Support Staff', 'guard_name' => 'admin']);
        $this->unprivilegedStaff = Staff::create([
            'staff_id' => 'STF' . date('YmdHis') . rand(100, 999),
            'title_id' => 1,
            'gender_id' => 1,
            'lga_id' => 1,
            'first_name' => 'Support',
            'last_name' => 'Staff',
            'email' => 'staff_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status_id' => 1,
            'mobile_number' => '080' . rand(10000000, 99999999),
            'created_by' => 'system',
            'updated_by' => 'system',
        ]);
        $this->unprivilegedStaff->assignRole($staffRole);
    }

    /** Helper to get headers for trusted device auth */
    protected function userHeaders(User $user, string $deviceId = 'DEV_TEST_123'): array
    {
        UserDevice::firstOrCreate([
            'user_id' => $user->user_id,
            'device_id' => $deviceId,
        ], [
            'device_name' => 'Tester Phone',
            'device_type' => 'android',
            'is_trusted' => true,
            'verified_at' => now(),
            'last_active_at' => now(),
        ]);

        return [
            'X-Device-ID' => $deviceId,
            'Accept' => 'application/json',
        ];
    }

    /** 1. Authentication Boundary & Guard Isolation */
    public function test_authentication_boundary_guard_isolation(): void
    {
        $userToken = $this->userA->createToken('user_token')->plainTextToken;

        // User token calling admin endpoint fails with 401
        $response = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401);
    }

    /** 2. Admin vs Customer Guard Isolation */
    public function test_admin_token_rejected_on_customer_routes_and_vice_versa(): void
    {
        $adminToken = $this->admin->createToken('admin_token')->plainTextToken;

        // Admin token calling user endpoint fails with 401
        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/v1/user/user-profile');

        $response->assertStatus(401);
    }

    /** 3. Trusted-Device Ownership Isolation */
    public function test_trusted_device_ownership_isolation(): void
    {
        // Device registered for User B
        UserDevice::create([
            'user_id' => $this->userB->user_id,
            'device_id' => 'DEV_USER_B_ONLY',
            'device_name' => 'User B Phone',
            'is_trusted' => true,
            'verified_at' => now(),
        ]);

        // User A attempts to authenticate using User B's device ID
        $response = $this->actingAs($this->userA, 'user')
            ->withHeader('X-Device-ID', 'DEV_USER_B_ONLY')
            ->getJson('/api/v1/user/user-profile');

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /** 4. Physical Device Invalid Token Rejection */
    public function test_physical_device_invalid_token_rejection(): void
    {
        $response = $this->withHeader('X-Device-Token', 'INVALID_PHYSICAL_TOKEN_999')
            ->postJson('/api/v1/device-integration/heartbeat', []);

        $response->assertStatus(401);
    }

    /** 5. Revoked Physical Credential Rejection */
    public function test_physical_device_deactivated_credentials_rejection(): void
    {
        $device = Device::create([
            'serial_number' => 'SN_PHYS_' . uniqid(),
            'imei' => 'IMEI_' . rand(100000, 999999),
            'registered_by' => $this->userA->user_id,
            'created_by' => $this->userA->user_id,
            'status' => DeviceStatus::Unactivated,
        ]);

        $integrationService = app(\App\Services\Integration\DeviceIntegrationService::class);
        $creds = $integrationService->issueCredentials($device);
        $integrationService->revokeCredentials($device);

        $response = $this->withHeader('X-Device-Token', $creds['device_token'])
            ->postJson('/api/v1/device-integration/heartbeat', []);

        $response->assertStatus(401);
    }

    /** 6. IDOR / BOLA Profile Update Protection */
    public function test_idor_profile_update_rejected_with_403(): void
    {
        // User A attempts to update User B's profile
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->putJson("/api/v1/user/update/{$this->userB->user_id}", [
                'titleId' => 1,
                'firstName' => 'HACKED',
                'lastName' => 'USER',
                'emailAddress' => 'hacked_' . uniqid() . '@example.com',
                'mobileNumber' => '+234809' . rand(1000000, 9999999),
                'statusId' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertNotEquals('HACKED', $this->userB->fresh()->first_name);
    }

    /** 7. IDOR / BOLA Passport Update Protection */
    public function test_idor_passport_update_rejected_with_403(): void
    {
        $fakeFile = UploadedFile::fake()->image('passport.jpg');

        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->postJson("/api/v1/user/user-passport/{$this->userB->user_id}", [
                'passport' => $fakeFile,
            ]);

        $response->assertStatus(403);
    }

    /** 8. RBAC Enforcement on Staff Users */
    public function test_rbac_unprivileged_staff_blocked_from_user_management(): void
    {
        $response = $this->actingAs($this->unprivilegedStaff, 'admin')
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    /** 9. Mass Assignment Protection for Non-Admins */
    public function test_mass_assignment_status_field_protected_from_customer(): void
    {
        $originalStatus = $this->userA->status_id;

        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->putJson("/api/v1/user/update/{$this->userA->user_id}", [
                'titleId' => 1,
                'firstName' => 'ValidName',
                'lastName' => 'ValidLast',
                'emailAddress' => $this->userA->email,
                'mobileNumber' => '+2348012345678',
                'statusId' => 3, // Attempting to alter status_id
            ]);

        $response->assertStatus(200);
        $this->assertEquals($originalStatus, $this->userA->fresh()->status_id);
    }

    /** 10. Sensitive Fields Hidden from API Resources */
    public function test_sensitive_fields_hidden_from_api_resources(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->userA->user_id,
            'type' => 'card',
            'gateway' => 'null_provider',
            'gateway_token' => 'RAW_SECRET_TOKEN_555',
            'last_four' => '4242',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2030,
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson('/api/v1/user/billing/payment-methods');

        $response->assertStatus(200);
        $response->assertJsonMissing(['gateway_token' => 'RAW_SECRET_TOKEN_555']);
    }

    /** 11. Rate Limiting Protection */
    public function test_rate_limiting_on_sensitive_auth_routes(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/user/auth/login', [
                'email' => 'wrong_' . $i . '@example.com',
                'password' => 'wrongpass',
            ]);
        }

        $response->assertStatus(429); // Too Many Requests
    }

    /** 12. Device Command Creation Authorization */
    public function test_device_command_creation_authorization(): void
    {
        $deviceB = Device::create([
            'serial_number' => 'SN_B_' . uniqid(),
            'imei' => 'IMEI_' . rand(100000, 999999),
            'registered_by' => $this->userB->user_id,
            'created_by' => $this->userB->user_id,
            'status' => DeviceStatus::Active,
        ]);

        DeviceAssignment::create([
            'device_id' => $deviceB->device_id,
            'user_id' => $this->userB->user_id,
            'assigned_by' => $this->userB->user_id,
            'assigned_by_type' => 'user',
            'status' => DeviceAssignmentStatus::Active,
            'assigned_at' => now(),
        ]);

        // User A attempts to command User B's device
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->postJson("/api/v1/user/devices/{$deviceB->device_id}/commands", [
                'command_type' => 'locate_now',
            ]);

        $response->assertStatus(403);
    }

    /** 13. Subscription Authorization Regression */
    public function test_subscription_authorization_regression(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Plan ' . uniqid(),
            'slug' => 'plan-' . uniqid(),
            'description' => 'Test plan',
            'price_monthly' => 1000,
            'price_yearly' => 10000,
            'currency' => 'NGN',
            'max_devices' => 1,
            'max_family_members' => 1,
            'is_active' => true,
        ]);

        $subB = UserSubscription::create([
            'user_id' => $this->userB->user_id,
            'plan_id' => $plan->plan_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // User A attempts to view User B's subscription
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson("/api/v1/user/subscriptions/{$subB->subscription_id}");

        $response->assertStatus(403);
    }

    /** 14. Webhook Security Regression */
    public function test_webhook_security_regression(): void
    {
        $response = $this->postJson('/api/v1/webhooks/billing', [
            'event' => 'payment.success',
            'data' => ['reference' => 'TXN_TEST'],
        ], [
            'X-Webhook-Signature' => 'invalid',
        ]);

        $response->assertStatus(403);
    }

    /** 15. Audit Log Protection from User Forgery */
    public function test_audit_log_protection_from_user_forgery(): void
    {
        // Ordinary user cannot call admin audit logs route
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(401);
    }

    /** 16. Error Information Disclosure Sanitization */
    public function test_error_handling_sanitizes_production_responses(): void
    {
        config(['app.debug' => false]);

        // Access non-existent endpoint
        $response = $this->getJson('/api/v1/non-existent-route-999');

        $response->assertStatus(404);
        $response->assertJsonMissing(['exception', 'trace']);
    }

    /** 17. Cross-User Data Isolation */
    public function test_cross_user_data_isolation(): void
    {
        $deviceB = Device::create([
            'serial_number' => 'SN_ISOLATE_' . uniqid(),
            'imei' => 'IMEI_' . rand(100000, 999999),
            'registered_by' => $this->userB->user_id,
            'created_by' => $this->userB->user_id,
            'status' => DeviceStatus::Active,
        ]);

        $alertB = Alert::create([
            'user_id' => $this->userB->user_id,
            'device_id' => $deviceB->device_id,
            'type' => 'geofence_exit',
            'severity' => AlertSeverity::Critical,
            'title' => 'Geofence Exit',
            'body' => 'Device left area',
            'triggered_at' => now(),
            'status' => AlertStatus::Unread,
        ]);

        // User A attempts to view User B's alert
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson("/api/v1/user/alerts/{$alertB->alert_id}");

        $response->assertStatus(403);
    }

    /** 18. Cross-Family Data Isolation */
    public function test_cross_family_data_isolation(): void
    {
        $familyB = Family::create([
            'owner_user_id' => $this->userB->user_id,
            'name' => 'Family B',
            'invite_code' => 'INV_B_' . Str::random(6),
            'max_members' => 5,
        ]);

        // User A attempts to view Family B details
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson("/api/v1/user/families/{$familyB->family_id}");

        $response->assertStatus(403);
    }

    /** 19. Admin Permission Isolation */
    public function test_admin_permission_isolation(): void
    {
        // Staff without role permission is rejected from updating roles
        $response = $this->actingAs($this->unprivilegedStaff, 'admin')
            ->getJson('/api/v1/admin/role');

        $response->assertStatus(403);
    }

    /** 20. Raw Passwords & Secrets Non-Exposure */
    public function test_raw_passwords_and_secrets_non_exposure(): void
    {
        $response = $this->actingAs($this->userA, 'user')
            ->withHeaders($this->userHeaders($this->userA))
            ->getJson('/api/v1/user/user-profile');

        $response->assertStatus(200);
        $response->assertJsonMissing(['password', 'remember_token']);
    }

    /** 21. Queue Payload Sensitive Data Protection */
    public function test_queue_payload_sensitive_data_protection(): void
    {
        $job = new \App\Jobs\ExpireSubscriptionsJob();
        $serialized = serialize($job);

        $this->assertStringNotContainsString('password', strtolower($serialized));
        $this->assertStringNotContainsString('secret_key', strtolower($serialized));
    }
}
