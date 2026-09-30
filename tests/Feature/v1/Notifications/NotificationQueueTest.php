<?php

namespace Tests\Feature\v1\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_send_notification_job_delivers_in_app_notification()
    {
        $user = User::factory()->create();

        $notification = OsafeNotification::create([
            'user_id' => $user->user_id,
            'title' => 'Job Delivery Title',
            'body' => 'Job Delivery Body',
            'channel' => 'in_app',
            'status' => 'pending',
            'is_sent' => false,
        ]);

        $job = new SendNotificationJob($notification->notification_id);
        $job->handle(app(NotificationService::class));

        $notification->refresh();
        $this->assertEquals('sent', $notification->status);
        $this->assertTrue($notification->is_sent);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_push_channel_returns_provider_unavailable_when_unconfigured()
    {
        $user = User::factory()->create();

        $notification = OsafeNotification::create([
            'user_id' => $user->user_id,
            'title' => 'Push Notification',
            'body' => 'Push Body',
            'channel' => 'push',
            'status' => 'pending',
            'is_sent' => false,
        ]);

        $job = new SendNotificationJob($notification->notification_id);
        $job->handle(app(NotificationService::class));

        $notification->refresh();
        $this->assertEquals('provider_unavailable', $notification->status);
        $this->assertFalse($notification->is_sent);
        $this->assertEquals('Push notification provider not configured.', $notification->failure_reason);
    }
}
