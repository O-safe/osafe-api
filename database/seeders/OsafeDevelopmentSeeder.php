<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceAssignmentStatus;
use App\Enums\DeviceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\Geofence;
use App\Models\Location\DeviceLocation;
use App\Models\Notification\Alert;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupLga;
use App\Models\Setup\SetupStatus;
use App\Models\Setup\SetupTitle;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class OsafeDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatus = SetupStatus::where('status_name', 'ACTIVE')->value('status_id') ?? SetupStatus::query()->min('status_id') ?? 1;
        $defaultTitle = SetupTitle::query()->min('title_id') ?? SetupTitle::query()->value('title_id') ?? 1;
        $defaultGender = SetupGender::query()->min('gender_id') ?? SetupGender::query()->value('gender_id') ?? 1;
        $defaultLga = SetupLga::query()->min('lga_id') ?? SetupLga::query()->value('lga_id') ?? 1;

        // Fetch or create primary demo user
        $demoUser = User::where('email', 'user@osafe.test')->first() ?? User::factory()->create([
            'email' => 'user@osafe.test',
            'first_name' => 'Demo',
            'last_name' => 'Customer',
        ]);

        // Secondary family user
        $familyMemberUser = User::firstOrCreate(
            ['email' => 'child@osafe.test'],
            [
                'user_id' => 'USR99920260922000002',
                'title_id' => $defaultTitle,
                'gender_id' => $defaultGender,
                'lga_id' => $defaultLga,
                'created_by' => 'system',
                'updated_by' => 'system',
                'first_name' => 'Junior',
                'last_name' => 'Customer',
                'password' => 'password123',
                'status_id' => $activeStatus,
                'mobile_number' => '08000000003',
            ]
        );

        // 1. Create Family
        $family = Family::firstOrCreate(
            ['name' => 'Demo Safety Group', 'owner_user_id' => $demoUser->user_id],
            [
                'max_members' => 6,
                'status_id' => $activeStatus,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        // Family Members
        FamilyMember::firstOrCreate([
            'family_id' => $family->family_id,
            'user_id' => $demoUser->user_id,
        ], [
            'role' => 'owner',
            'joined_at' => now(),
            'status_id' => $activeStatus,
        ]);

        FamilyMember::firstOrCreate([
            'family_id' => $family->family_id,
            'user_id' => $familyMemberUser->user_id,
        ], [
            'role' => 'child',
            'joined_at' => now(),
            'status_id' => $activeStatus,
        ]);

        // 2. User Subscription
        $familyPlan = SubscriptionPlan::where('slug', 'family')->first();
        if ($familyPlan) {
            UserSubscription::firstOrCreate(
                ['user_id' => $demoUser->user_id, 'plan_id' => $familyPlan->plan_id],
                [
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => now()->subDays(15),
                    'ends_at' => now()->addDays(15),
                    'auto_renew' => true,
                ]
            );
        }

        // 3. Physical Devices
        $device1 = Device::firstOrCreate(
            ['serial_number' => 'SN-DEMOBAND001'],
            [
                'imei' => '860123456789012',
                'name' => 'Demo Tracker Band',
                'model' => 'O SAFE Band Pro v2',
                'firmware_version' => '1.4.2',
                'status' => DeviceStatus::Active,
                'battery_level' => 88,
                'battery_status' => 'discharging',
                'last_seen_at' => now(),
                'registered_by' => $demoUser->user_id,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        $device2 = Device::firstOrCreate(
            ['serial_number' => 'SN-DEMOWATCH02'],
            [
                'imei' => '860123456789099',
                'name' => 'Child Watch',
                'model' => 'O SAFE Watch Kid v1',
                'firmware_version' => '1.1.0',
                'status' => DeviceStatus::Active,
                'battery_level' => 35,
                'battery_status' => 'discharging',
                'last_seen_at' => now(),
                'registered_by' => $demoUser->user_id,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        // Device Assignments
        DeviceAssignment::firstOrCreate(
            ['device_id' => $device1->device_id, 'user_id' => $demoUser->user_id],
            [
                'family_id' => $family->family_id,
                'assigned_by' => $demoUser->user_id,
                'assigned_by_type' => 'user',
                'status' => DeviceAssignmentStatus::Active,
                'assigned_at' => now()->subDays(10),
                'metadata' => ['notes' => 'Primary owner personal tracker.'],
            ]
        );

        DeviceAssignment::firstOrCreate(
            ['device_id' => $device2->device_id, 'user_id' => $familyMemberUser->user_id],
            [
                'family_id' => $family->family_id,
                'assigned_by' => $demoUser->user_id,
                'assigned_by_type' => 'user',
                'status' => DeviceAssignmentStatus::Active,
                'assigned_at' => now()->subDays(10),
                'metadata' => ['notes' => 'Child safety watch tracker.'],
            ]
        );

        // 4. Device Locations (Lagos demo coordinates)
        DeviceLocation::create([
            'device_id' => $device1->device_id,
            'user_id' => $demoUser->user_id,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
            'accuracy' => 5.0,
            'altitude' => 15.0,
            'speed' => 0.0,
            'heading' => 0.0,
            'source' => 'gps',
            'is_mock' => false,
            'address' => 'Ikeja, Lagos, Nigeria',
            'recorded_at' => now(),
            'received_at' => now(),
        ]);

        DeviceLocation::create([
            'device_id' => $device2->device_id,
            'user_id' => $familyMemberUser->user_id,
            'latitude' => 6.5300,
            'longitude' => 3.3850,
            'accuracy' => 8.0,
            'altitude' => 12.0,
            'speed' => 1.2,
            'heading' => 45.0,
            'source' => 'gps',
            'is_mock' => false,
            'address' => 'Maryland, Lagos, Nigeria',
            'recorded_at' => now(),
            'received_at' => now(),
        ]);

        // 5. Geofence
        $geofence = Geofence::firstOrCreate(
            ['owner_user_id' => $demoUser->user_id, 'name' => 'Demo Home Zone'],
            [
                'family_id' => $family->family_id,
                'center_latitude' => 6.5244,
                'center_longitude' => 3.3792,
                'radius_meters' => 300,
                'shape' => 'circle',
                'alert_on_entry' => true,
                'alert_on_exit' => true,
                'description' => 'Development demo home perimeter geofence.',
                'is_active' => true,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        // Attach device to geofence
        $geofence->devices()->syncWithoutDetaching([$device1->device_id, $device2->device_id]);

        // 6. Alerts
        Alert::firstOrCreate(
            ['device_id' => $device2->device_id, 'title' => 'Low Battery Warning'],
            [
                'user_id' => $demoUser->user_id,
                'type' => 'low_battery',
                'severity' => AlertSeverity::Warning,
                'status' => AlertStatus::Unread,
                'body' => 'Device battery level reached 35%.',
                'triggered_at' => now()->subMinutes(20),
            ]
        );

        // 7. Support Ticket
        SupportTicket::firstOrCreate(
            ['ticket_number' => 'TKT-20260922-0001'],
            [
                'user_id' => $demoUser->user_id,
                'device_id' => $device2->device_id,
                'subject' => 'Question regarding geofence notifications',
                'description' => 'I would like to know how often geofence exit alerts trigger when inside quiet hours.',
                'category' => 'geofence',
                'priority' => 'medium',
                'status' => SupportTicketStatus::Open,
            ]
        );
    }
}
