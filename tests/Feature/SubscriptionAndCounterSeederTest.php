<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAndCounterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_plans_counters_and_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify subscription plans
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'standard']);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'family']);

        // 2. Verify setup counters
        $this->assertDatabaseHas('setup_counters', ['counter_id' => 'USR']);
        $this->assertDatabaseHas('setup_counters', ['counter_id' => 'STF']);
        $this->assertDatabaseHas('setup_counters', ['counter_id' => 'DEV']);
        $this->assertDatabaseHas('setup_counters', ['counter_id' => 'FAM']);
        $this->assertDatabaseHas('setup_counters', ['counter_id' => 'TKT']);

        // 3. Verify Staff & Demo User
        $this->assertDatabaseHas('staff', ['email' => 'admin@osafe.test']);
        $this->assertDatabaseHas('users', ['email' => 'user@osafe.test']);

        // 4. Verify Demo Hierarchy Data
        $this->assertDatabaseHas('families', ['name' => 'Demo Safety Group']);
        $this->assertDatabaseHas('devices', ['serial_number' => 'SN-DEMOBAND001']);
        $this->assertDatabaseHas('geofences', ['name' => 'Demo Home Zone']);
        $this->assertDatabaseHas('alerts', ['title' => 'Low Battery Warning']);
        $this->assertDatabaseHas('support_tickets', ['ticket_number' => 'TKT-20260922-0001']);
    }
}
