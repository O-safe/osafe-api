<?php

namespace Tests\Feature;

use App\Http\Resources\Device\DeviceResource;
use App\Http\Resources\Family\FamilyInvitationResource;
use App\Http\Resources\Subscription\PaymentMethodResource;
use App\Http\Resources\User\UserResource;
use App\Models\Device\Device;
use App\Models\Family\Family;
use App\Models\Family\FamilyInvitation;
use App\Models\Subscription\PaymentMethod;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiResourceSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_fields_are_absent_from_api_resources(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::factory()->create();

        // 1. Payment Method: gateway_token omitted
        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->user_id,
            'type' => 'card',
            'gateway' => 'paystack',
            'gateway_token' => 'SECRET_TOKEN_99182',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2028,
        ]);

        $paymentArray = (new PaymentMethodResource($paymentMethod))->toArray(new Request());
        $this->assertArrayNotHasKey('gateway_token', $paymentArray);
        $this->assertArrayNotHasKey('token', $paymentArray);

        // 2. Family Invitation: secret invitation token omitted
        $family = Family::factory()->create(['owner_user_id' => $user->user_id]);
        $invitation = FamilyInvitation::create([
            'family_id' => $family->family_id,
            'invited_by' => $user->user_id,
            'invitee_email' => 'test@invitee.com',
            'token' => 'INVITE_SECRET_98127398',
            'role' => 'member',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $inviteArray = (new FamilyInvitationResource($invitation))->toArray(new Request());
        $this->assertArrayNotHasKey('token', $inviteArray);
    }

    public function test_relationships_are_serialized_when_loaded_and_absent_when_not_loaded(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $device = Device::factory()->create();

        // 1. Unloaded relationship
        $unloadedResource = (new DeviceResource($device))->toArray(new Request());
        $this->assertInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $unloadedResource['geofences']);

        // 2. Loaded relationship
        $device->load('geofences');
        $loadedResource = (new DeviceResource($device))->toArray(new Request());
        $this->assertNotInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $loadedResource['geofences']);
    }

    public function test_enums_serialize_to_string_values_correctly(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $device = Device::factory()->active()->create();
        $resource = (new DeviceResource($device))->toArray(new Request());

        $this->assertEquals('active', $resource['status']);
    }

    public function test_resource_collection_pagination_compatibility(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        Device::factory()->count(5)->create();

        $paginator = Device::paginate(2);
        $collection = DeviceResource::collection($paginator)->response()->getData(true);

        $this->assertArrayHasKey('data', $collection);
        $this->assertArrayHasKey('links', $collection);
        $this->assertArrayHasKey('meta', $collection);
        $this->assertCount(2, $collection['data']);
    }
}
