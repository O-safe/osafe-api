<?php

namespace Tests\Feature;

use App\Exceptions\FamilyLimitExceededException;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use App\Services\Family\FamilyMemberService;
use App\Services\Family\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FamilyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_family_and_first_member_is_owner(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $familyService = app(FamilyService::class);

        $family = $familyService->createFamily($owner, [
            'name' => 'The Smith Family',
            'max_members' => 3,
        ]);

        $this->assertEquals($owner->user_id, $family->owner_user_id);
        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->family_id,
            'user_id' => $owner->user_id,
            'role' => 'owner',
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $family));
        $this->assertTrue(Gate::forUser($owner)->allows('manageMembers', $family));
    }

    public function test_ordinary_member_cannot_perform_owner_only_operations(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $family = Family::factory()->create(['owner_user_id' => $owner->user_id]);
        FamilyMember::factory()->create([
            'family_id' => $family->family_id,
            'user_id' => $memberUser->user_id,
            'role' => 'member',
        ]);

        $this->assertFalse(Gate::forUser($memberUser)->allows('delete', $family));
        $this->assertFalse(Gate::forUser($memberUser)->allows('manageMembers', $family));
    }

    public function test_max_member_limit_is_enforced(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $familyService = app(FamilyService::class);
        $memberService = app(FamilyMemberService::class);

        $family = $familyService->createFamily($owner, [
            'name' => 'Small Family',
            'max_members' => 2,
        ]);

        $user2 = User::factory()->create();
        $memberService->addMember($owner, $family, $user2, 'member');

        $user3 = User::factory()->create();

        $this->expectException(FamilyLimitExceededException::class);
        $memberService->addMember($owner, $family, $user3, 'member');
    }
}
