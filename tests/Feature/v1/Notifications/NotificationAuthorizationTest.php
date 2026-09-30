<?php

namespace Tests\Feature\v1\Notifications;

use App\Models\Admin\UserDevice;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function authenticateUser(User $user): array
    {
        $deviceId = 'TEST_DEV_' . uniqid();
        UserDevice::create([
            'user_id' => $user->user_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $tokenResult = $user->createToken('auth_token');
        $tokenResult->accessToken->device_id = $deviceId;
        $tokenResult->accessToken->save();

        return [
            'Authorization' => 'Bearer ' . $tokenResult->plainTextToken,
            'X-Device-ID' => $deviceId,
        ];
    }

    public function test_user_can_access_only_their_own_notifications()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $notifA = OsafeNotification::create([
            'user_id' => $userA->user_id,
            'title' => 'User A Notif',
            'body' => 'Body text',
            'channel' => 'in_app',
            'status' => 'sent',
        ]);

        $notifB = OsafeNotification::create([
            'user_id' => $userB->user_id,
            'title' => 'User B Notif',
            'body' => 'Body text',
            'channel' => 'in_app',
            'status' => 'sent',
        ]);

        $headersA = $this->authenticateUser($userA);

        // User A list notifications -> contains only notifA
        $indexResponse = $this->getJson('/api/v1/user/notifications', $headersA);
        $indexResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.notification_id', $notifA->notification_id);

        // User A cannot mark User B's notification as read
        $markReadResponse = $this->postJson("/api/v1/user/notifications/{$notifB->notification_id}/read", [], $headersA);
        $markReadResponse->assertStatus(404);
    }
}
