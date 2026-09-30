<?php

namespace Tests\Feature\v1\Notifications;

use App\Http\Resources\Alert\NotificationResource;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPayloadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_notification_resource_contains_no_sensitive_fields()
    {
        $user = User::factory()->create();

        $notification = OsafeNotification::create([
            'user_id' => $user->user_id,
            'title' => 'Security Audit Notification',
            'body' => 'Safety verification passed.',
            'channel' => 'in_app',
            'status' => 'sent',
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        $resource = new NotificationResource($notification);
        $array = $resource->toArray(request());

        $forbiddenKeys = [
            'password',
            'password_hash',
            'remember_token',
            'mfa_secret',
            'secret',
            'payment_token',
            'api_key_hash',
            'token',
            'credentials',
        ];

        foreach ($forbiddenKeys as $key) {
            $this->assertArrayNotHasKey($key, $array, "Notification Resource exposes forbidden key {$key}");
        }
    }
}
