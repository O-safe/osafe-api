<?php

namespace Tests\Unit;

use App\Models\Family\Family;
use App\Models\Family\FamilyInvitation;
use App\Models\Integration\DeviceIntegration;
use App\Models\Subscription\PaymentMethod;
use App\Models\System\MfaMethod;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SensitiveFieldEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_fields_are_encrypted_in_database_and_hidden_from_json(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::factory()->create();

        // 1. MFA Method Secret
        $mfa = MfaMethod::create([
            'user_id' => $user->user_id,
            'type' => 'totp',
            'secret' => 'JBSWY3DPEHPK3PXP',
            'is_primary' => true,
        ]);

        // Raw SQL check: secret must NOT be plaintext 'JBSWY3DPEHPK3PXP'
        $rawMfa = DB::table('mfa_methods')->where('mfa_method_id', $mfa->mfa_method_id)->first();
        $this->assertNotEquals('JBSWY3DPEHPK3PXP', $rawMfa->secret);
        // Decrypted model accessor check:
        $this->assertEquals('JBSWY3DPEHPK3PXP', $mfa->fresh()->secret);
        // Serialization check: secret must be hidden from array / JSON
        $this->assertArrayNotHasKey('secret', $mfa->toArray());

        // 2. Payment Method Gateway Token
        $payment = PaymentMethod::create([
            'user_id' => $user->user_id,
            'type' => 'card',
            'gateway' => 'paystack',
            'gateway_token' => 'AUTH_8912389129831',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2028,
            'is_default' => true,
        ]);

        $rawPayment = DB::table('payment_methods')->where('payment_method_id', $payment->payment_method_id)->first();
        $this->assertNotEquals('AUTH_8912389129831', $rawPayment->gateway_token);
        $this->assertEquals('AUTH_8912389129831', $payment->fresh()->gateway_token);
        $this->assertArrayNotHasKey('gateway_token', $payment->toArray());

        // 3. Family Invitation Token
        $family = Family::factory()->create(['owner_user_id' => $user->user_id]);
        $invitation = FamilyInvitation::create([
            'family_id' => $family->family_id,
            'invited_by' => $user->user_id,
            'invitee_email' => 'invitee@test.com',
            'token' => 'SECRET_INVITE_TOKEN_123',
            'role' => 'adult',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $rawInvite = DB::table('family_invitations')->where('invitation_id', $invitation->invitation_id)->first();
        $this->assertNotEquals('SECRET_INVITE_TOKEN_123', $rawInvite->token);
        $this->assertEquals('SECRET_INVITE_TOKEN_123', $invitation->fresh()->token);
        $this->assertArrayNotHasKey('token', $invitation->toArray());
    }
}
