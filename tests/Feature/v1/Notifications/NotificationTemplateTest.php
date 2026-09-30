<?php

namespace Tests\Feature\v1\Notifications;

use App\Models\Notification\NotificationTemplate;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_template_rendering_substitutes_safe_placeholders()
    {
        NotificationTemplate::create([
            'slug' => 'device_offline',
            'name' => 'Device {{device_name}} Status',
            'channel' => 'in_app',
            'subject' => 'Notice for {{device_name}}',
            'body' => 'Device {{device_name}} is now {{status}}.',
            'is_active' => true,
        ]);

        $service = app(NotificationService::class);
        $rendered = $service->renderTemplate('device_offline', [
            'device_name' => 'GPS Watch',
            'status' => 'offline',
        ], 'in_app');

        $this->assertEquals('Device GPS Watch Status', $rendered['title']);
        $this->assertEquals('Device GPS Watch is now offline.', $rendered['body']);
    }

    public function test_template_rendering_strips_sensitive_variable_keys()
    {
        NotificationTemplate::create([
            'slug' => 'sensitive_test',
            'name' => 'Alert',
            'channel' => 'in_app',
            'body' => 'Password is {{password}}, Token is {{token}}.',
            'is_active' => true,
        ]);

        $service = app(NotificationService::class);
        $rendered = $service->renderTemplate('sensitive_test', [
            'password' => 'SuperSecret123!',
            'token' => 'bearer_token_xyz',
            'device_name' => 'Safe Device',
        ], 'in_app');

        $this->assertStringNotContainsString('SuperSecret123!', $rendered['body']);
        $this->assertStringNotContainsString('bearer_token_xyz', $rendered['body']);
    }
}
