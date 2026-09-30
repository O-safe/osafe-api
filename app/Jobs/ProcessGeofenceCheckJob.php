<?php

namespace App\Jobs;

use App\Models\Device\Device;
use App\Models\Location\DeviceLocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGeofenceCheckJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30];
    public int $timeout = 120;

    public function __construct(
        public string $deviceId,
        public int $locationId
    ) {}

    public function handle(): void
    {
        $device = Device::find($this->deviceId);
        $location = DeviceLocation::find($this->locationId);

        if (!$device || !$location) {
            return;
        }

        // Integration hook for background geofence evaluation
    }
}
