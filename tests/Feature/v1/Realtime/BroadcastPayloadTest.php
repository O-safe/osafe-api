<?php

namespace Tests\Feature\v1\Realtime;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use App\Events\AlertCreated;
use App\Events\AlertStatusChanged;
use App\Events\DeviceBatteryUpdated;
use App\Events\DeviceCommandCreated;
use App\Events\DeviceCommandStatusChanged;
use App\Events\DeviceLocationUpdated;
use App\Events\DeviceStatusChanged;
use App\Events\FamilyMemberAdded;
use App\Events\FamilyMemberRemoved;
use App\Events\FamilyMemberRoleChanged;
use App\Events\GeofenceEntered;
use App\Events\GeofenceExited;
use App\Events\GeofenceViolation;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancelled;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\GeofenceEvent;
use App\Models\Location\DeviceLocation;
use App\Models\Notification\Alert;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastPayloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_all_broadcast_events_have_safe_compact_payloads()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);

        $command = new DeviceCommand([
            'command_id' => 1,
            'device_id' => $device->device_id,
            'issued_by_type' => 'user',
            'issued_by' => $user->user_id,
            'command_type' => DeviceCommandType::LocateNow,
            'status' => DeviceCommandStatus::Pending,
        ]);

        $alert = new Alert([
            'alert_id' => 1,
            'user_id' => $user->user_id,
            'device_id' => $device->device_id,
            'title' => 'Test Alert',
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::Unread,
            'triggered_at' => now(),
        ]);

        $member = new FamilyMember([
            'family_member_id' => 1,
            'family_id' => 1,
            'user_id' => $user->user_id,
            'role' => 'member',
            'relationship' => 'child',
            'joined_at' => now(),
        ]);

        $subscription = new UserSubscription([
            'subscription_id' => 1,
            'user_id' => $user->user_id,
            'plan_id' => 1,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $location = new DeviceLocation([
            'location_id' => 1,
            'device_id' => $device->device_id,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
            'accuracy' => 10.5,
            'speed' => 45.0,
            'recorded_at' => now(),
        ]);

        $geofenceEvent = new GeofenceEvent([
            'event_id' => 1,
            'geofence_id' => 1,
            'device_id' => $device->device_id,
            'event_type' => 'entry',
            'event_time' => now(),
        ]);

        $events = [
            new DeviceLocationUpdated($location, $user->user_id),
            new DeviceStatusChanged($device, 'online', 'offline'),
            new DeviceBatteryUpdated($device, 85, true),
            new DeviceCommandCreated($command),
            new DeviceCommandStatusChanged($command, 'pending', 'sent'),
            new GeofenceEntered($geofenceEvent, 1, $user->user_id),
            new GeofenceExited($geofenceEvent, 1, $user->user_id),
            new GeofenceViolation($geofenceEvent, 1, $user->user_id),
            new AlertCreated($alert),
            new AlertStatusChanged($alert, 'unread', 'read'),
            new FamilyMemberAdded($member),
            new FamilyMemberRemoved(1, $user->user_id),
            new FamilyMemberRoleChanged($member, 'member', 'admin'),
            new SubscriptionActivated($subscription),
            new SubscriptionCancelled($subscription),
        ];

        $sensitiveKeys = [
            'password',
            'password_hash',
            'remember_token',
            'mfa_secret',
            'secret',
            'payment_token',
            'api_key_hash',
            'token',
            'private_key',
            'credentials',
        ];

        foreach ($events as $event) {
            $payload = $event->broadcastWith();

            $this->assertIsArray($payload, "Payload for " . get_class($event) . " must be an array.");

            foreach ($payload as $key => $value) {
                foreach ($sensitiveKeys as $sensitive) {
                    $this->assertStringNotContainsString(
                        $sensitive,
                        strtolower($key),
                        "Event " . get_class($event) . " payload exposes forbidden sensitive field: {$key}"
                    );
                }
            }
        }
    }

    public function test_event_names_and_channels_are_correctly_formatted()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);

        $location = new DeviceLocation([
            'location_id' => 1,
            'device_id' => $device->device_id,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
            'recorded_at' => now(),
        ]);

        $event = new DeviceLocationUpdated($location, $user->user_id);

        $this->assertEquals('device.location_updated', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertEquals("private-device.{$device->device_id}", $channels[0]->name);
        $this->assertEquals("private-user.{$user->user_id}", $channels[1]->name);
    }
}
