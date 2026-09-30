<?php

namespace Tests\Feature\v1\Billing;

use App\Models\User\User;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\NullPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentProviderInterface $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->provider = app(PaymentProviderInterface::class);
    }

    public function test_payment_provider_instance_implements_interface(): void
    {
        $this->assertInstanceOf(PaymentProviderInterface::class, $this->provider);
        $this->assertNotEmpty($this->provider->getName());
    }

    public function test_payment_provider_initialize_and_verify(): void
    {
        $user = User::factory()->create();

        $init = $this->provider->initializePayment($user, 5000.00, 'NGN', ['reference' => 'TXN_TEST_123']);
        $this->assertArrayHasKey('reference', $init);
        $this->assertArrayHasKey('checkout_url', $init);

        $verify = $this->provider->verifyPayment('TXN_TEST_123');
        $this->assertEquals('successful', $verify['status']);
    }

    public function test_payment_provider_webhook_signature_and_parsing(): void
    {
        $requestValid = Request::create('/api/v1/webhooks/billing', 'POST', [], [], [], ['HTTP_X_WEBHOOK_SIGNATURE' => 'valid_sig']);
        $this->assertTrue($this->provider->verifyWebhookSignature($requestValid));

        $requestInvalid = Request::create('/api/v1/webhooks/billing', 'POST', [], [], [], ['HTTP_X_WEBHOOK_SIGNATURE' => 'invalid']);
        $this->assertFalse($this->provider->verifyWebhookSignature($requestInvalid));

        $parsed = $this->provider->parseWebhookPayload([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TXN_TEST_999',
                'amount' => 3000,
                'currency' => 'NGN',
            ],
        ]);

        $this->assertEquals('charge.success', $parsed['event_type']);
        $this->assertEquals('TXN_TEST_999', $parsed['reference']);
    }
}
