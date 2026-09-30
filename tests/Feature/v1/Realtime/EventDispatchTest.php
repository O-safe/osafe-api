<?php

namespace Tests\Feature\v1\Realtime;

use App\Enums\AlertSeverity;
use App\Events\AlertCreated;
use App\Events\DeviceCommandCreated;
use App\Events\DeviceStatusChanged;
use App\Events\FamilyMemberAdded;
use App\Events\SubscriptionActivated;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use App\Services\Alert\AlertService;
use App\Services\Device\DeviceCommandAuthorizationService;
use App\Services\Device\DeviceService;
use App\Services\Family\FamilyService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_device_status_changed_event_dispatched_on_device_activation()
    {
        Event::fake([DeviceStatusChanged::class]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);
        $deviceService = app(DeviceService::class);

        $deviceService->activateDevice($user, $device);

        Event::assertDispatched(DeviceStatusChanged::class, function ($event) use ($device) {
            return $event->device->device_id === $device->device_id && $event->newStatus === 'active';
        });
    }

    public function test_device_command_created_event_dispatched_on_command_creation()
    {
        Event::fake([DeviceCommandCreated::class]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id, 'is_activated' => true, 'status' => 'active']);
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $cmdService = app(DeviceCommandAuthorizationService::class);
        $command = $cmdService->authorizeAndQueueCommand($user, $device, 'locate_now');

        Event::assertDispatched(DeviceCommandCreated::class, function ($event) use ($command) {
            return $event->command->command_id === $command->command_id;
        });
    }

    public function test_alert_created_event_dispatched_on_alert_creation()
    {
        Event::fake([AlertCreated::class]);

        $user = User::factory()->create();
        $alertService = app(AlertService::class);

        $alert = $alertService->createAlert([
            'user_id' => $user->user_id,
            'title' => 'Test Hazard Alert',
            'body' => 'Test Hazard Body',
            'severity' => AlertSeverity::Critical,
        ]);

        Event::assertDispatched(AlertCreated::class, function ($event) use ($alert) {
            return $event->alert->alert_id === $alert->alert_id;
        });
    }

    public function test_family_member_added_event_dispatched_on_family_creation()
    {
        Event::fake([FamilyMemberAdded::class]);

        $user = User::factory()->create();
        $familyService = app(FamilyService::class);

        $family = $familyService->createFamily($user, ['name' => 'Safety Family']);

        Event::assertDispatched(FamilyMemberAdded::class, function ($event) use ($user) {
            return $event->member->user_id === $user->user_id;
        });
    }

    public function test_subscription_activated_event_dispatched_on_activation()
    {
        Event::fake([SubscriptionActivated::class]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subService = app(SubscriptionService::class);

        $subscription = $subService->activateSubscription($user, $plan);

        Event::assertDispatched(SubscriptionActivated::class, function ($event) use ($subscription) {
            return $event->subscription->subscription_id === $subscription->subscription_id;
        });
    }

    public function test_events_implement_should_dispatch_after_commit()
    {
        $events = [
            \App\Events\DeviceStatusChanged::class,
            \App\Events\DeviceCommandCreated::class,
            \App\Events\AlertCreated::class,
            \App\Events\FamilyMemberAdded::class,
            \App\Events\SubscriptionActivated::class,
        ];

        foreach ($events as $eventClass) {
            $interfaces = class_implements($eventClass);
            $this->assertArrayHasKey(
                ShouldDispatchAfterCommit::class,
                $interfaces,
                "Event {$eventClass} must implement ShouldDispatchAfterCommit interface."
            );
        }
    }

    public function test_no_event_dispatched_when_database_transaction_rolls_back()
    {
        Event::fake([FamilyMemberAdded::class]);

        $user = User::factory()->create();
        $familyService = app(FamilyService::class);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $familyService) {
                $familyService->createFamily($user, ['name' => 'Failed Transaction Family']);
                throw new \Exception('Simulated database transaction failure.');
            });
        } catch (\Exception $e) {
            // Expected transaction rollback exception
        }

        Event::assertNotDispatched(FamilyMemberAdded::class);
    }
}
