<?php

namespace Tests\Feature\v1\Notifications;

use App\Events\AlertCreated;
use App\Listeners\SendAlertNotificationListener;
use App\Models\Notification\Alert;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_notification_service_creates_in_app_notification_record()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $notification = $service->createAndDeliver(
            user: $user,
            notificationType: 'alert',
            title: 'Critical SOS Alert',
            body: 'SOS triggered near Victoria Island.',
            channel: 'in_app'
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('osafe_notifications', [
            'user_id' => $user->user_id,
            'title' => 'Critical SOS Alert',
            'channel' => 'in_app',
            'status' => 'pending',
        ]);
    }

    public function test_alert_created_listener_triggers_notification_creation_for_correct_recipient()
    {
        $user = User::factory()->create();
        $alert = Alert::create([
            'user_id' => $user->user_id,
            'title' => 'Panic Alert',
            'body' => 'User activated panic button',
            'severity' => 'critical',
            'type' => 'sos',
            'status' => 'unread',
        ]);

        $listener = app(SendAlertNotificationListener::class);
        $listener->handle(new AlertCreated($alert));

        $this->assertDatabaseHas('osafe_notifications', [
            'user_id' => $user->user_id,
            'alert_id' => $alert->alert_id,
            'channel' => 'in_app',
        ]);
    }

    public function test_duplicate_notification_creation_within_window_is_suppressed()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $notif1 = $service->createAndDeliver($user, 'alert', 'Test Duplicate Title', 'Body Text', 'in_app');
        $notif2 = $service->createAndDeliver($user, 'alert', 'Test Duplicate Title', 'Body Text', 'in_app');

        $this->assertEquals($notif1->notification_id, $notif2->notification_id);
        $this->assertEquals(1, OsafeNotification::where('user_id', $user->user_id)->where('title', 'Test Duplicate Title')->count());
    }
}
