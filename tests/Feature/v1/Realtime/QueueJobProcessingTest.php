<?php

namespace Tests\Feature\v1\Realtime;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use App\Jobs\GenerateReportJob;
use App\Jobs\ProcessDeviceCommandJob;
use App\Jobs\ProcessGeofenceCheckJob;
use App\Jobs\SendNotificationJob;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueJobProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_queue_jobs_can_be_dispatched_and_pushed_to_queue()
    {
        Queue::fake();

        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);
        $command = DeviceCommand::create([
            'device_id' => $device->device_id,
            'issued_by_type' => 'user',
            'issued_by' => $user->user_id,
            'command_type' => DeviceCommandType::LocateNow,
            'status' => DeviceCommandStatus::Pending,
        ]);

        ProcessDeviceCommandJob::dispatch((string) $command->command_id);
        SendNotificationJob::dispatch(1);
        ProcessGeofenceCheckJob::dispatch($device->device_id, 6.52, 3.37);
        GenerateReportJob::dispatch(1);

        Queue::assertPushed(ProcessDeviceCommandJob::class);
        Queue::assertPushed(SendNotificationJob::class);
        Queue::assertPushed(ProcessGeofenceCheckJob::class);
        Queue::assertPushed(GenerateReportJob::class);
    }

    public function test_queue_jobs_have_retry_and_backoff_configuration()
    {
        $commandJob = new ProcessDeviceCommandJob('1');
        $notifJob = new SendNotificationJob(1);
        $geoJob = new ProcessGeofenceCheckJob('dev1', 6.5, 3.3);
        $reportJob = new GenerateReportJob(1);

        $jobs = [$commandJob, $notifJob, $geoJob, $reportJob];

        foreach ($jobs as $job) {
            $this->assertObjectHasProperty('tries', $job, "Job " . get_class($job) . " must define max tries.");
            $this->assertObjectHasProperty('backoff', $job, "Job " . get_class($job) . " must define backoff.");
            $this->assertGreaterThan(0, $job->tries);
            $this->assertNotNull($job->backoff);
        }
    }

    public function test_process_device_command_job_handles_backend_state_without_hardware_mocking()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);
        $command = DeviceCommand::create([
            'device_id' => $device->device_id,
            'issued_by_type' => 'user',
            'issued_by' => $user->user_id,
            'command_type' => DeviceCommandType::LocateNow,
            'status' => DeviceCommandStatus::Pending,
        ]);

        $job = new ProcessDeviceCommandJob((string) $command->command_id);
        $job->handle();

        $command->refresh();
        $this->assertEquals(DeviceCommandStatus::Sent, $command->status);
    }
}
