<?php

namespace Tests\Feature\v1\Billing;

use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\Subscription\PaymentMethod;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentMethodSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_gateway_token_is_encrypted_in_db_and_hidden_from_resource(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::create([
            'user_id' => $user->user_id,
            'type' => 'card',
            'gateway' => 'paystack',
            'gateway_token' => 'SECRET_TOKEN_GW_999',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2028,
            'is_default' => true,
        ]);

        // Raw SQL check: secret token MUST NOT be plaintext in raw database row
        $rawRow = DB::table('payment_methods')->where('payment_method_id', $pm->payment_method_id)->first();
        $this->assertNotEquals('SECRET_TOKEN_GW_999', $rawRow->gateway_token);

        // Accessor check: model dereferences decrypted value
        $this->assertEquals('SECRET_TOKEN_GW_999', $pm->fresh()->gateway_token);

        // Serialization check: gateway_token MUST BE absent from array / JSON response
        $this->assertArrayNotHasKey('gateway_token', $pm->toArray());

        $admin = Staff::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();
        $deviceId = 'DEV_ADMIN_PM_' . uniqid();
        UserDevice::create([
            'user_id' => $admin->staff_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->getJson('/api/v1/admin/billing/payment-methods');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertStringNotContainsString('SECRET_TOKEN_GW_999', json_encode($json));
    }
}
