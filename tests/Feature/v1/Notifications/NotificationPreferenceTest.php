<?php

namespace Tests\Feature\v1\Notifications;

use App\Models\Admin\UserDevice;
use App\Models\Notification\NotificationPreference;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
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

    public function test_notification_suppressed_when_disabled_by_user_preference()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        NotificationPreference::create([
            'user_id' => $user->user_id,
            'notification_type' => 'device_status',
            'channel' => 'in_app',
            'enabled' => false,
        ]);

        $notification = $service->createAndDeliver($user, 'device_status', 'Device Offline', 'Device is offline', 'in_app');

        $this->assertNull($notification);
        $this->assertDatabaseMissing('osafe_notifications', [
            'user_id' => $user->user_id,
            'title' => 'Device Offline',
        ]);
    }

    public function test_user_can_update_and_retrieve_notification_preferences()
    {
        $user = User::factory()->create();
        $headers = $this->authenticateUser($user);

        $updateResponse = $this->putJson('/api/v1/user/notification-preferences', [
            'channel' => 'push',
            'notification_type' => 'geofence',
            'enabled' => false,
        ], $headers);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enabled', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->user_id,
            'channel' => 'push',
            'notification_type' => 'geofence',
            'enabled' => 0,
        ]);
    }
}
