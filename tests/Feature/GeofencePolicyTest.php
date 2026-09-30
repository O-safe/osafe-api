<?php

namespace Tests\Feature;

use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\Geofence;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GeofencePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_owner_can_manage_their_geofence(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $geofence = Geofence::factory()->create(['owner_user_id' => $owner->user_id]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $geofence));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $geofence));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $geofence));
    }

    public function test_unauthorized_user_cannot_modify_another_users_geofence(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $geofence = Geofence::factory()->create(['owner_user_id' => $owner->user_id]);

        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $geofence));
        $this->assertFalse(Gate::forUser($otherUser)->allows('update', $geofence));
        $this->assertFalse(Gate::forUser($otherUser)->allows('delete', $geofence));
    }
}
