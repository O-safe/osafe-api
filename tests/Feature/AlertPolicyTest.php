<?php

namespace Tests\Feature;

use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Notification\Alert;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AlertPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_authorized_alerts(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $user->user_id]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $alert));
        $this->assertTrue(Gate::forUser($user)->allows('resolve', $alert));
    }

    public function test_cross_user_alert_access_is_denied(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $userA->user_id]);

        $this->assertFalse(Gate::forUser($userB)->allows('view', $alert));
        $this->assertFalse(Gate::forUser($userB)->allows('resolve', $alert));
    }
}
