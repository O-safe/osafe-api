<?php

namespace App\Services\Integration;

use App\Contracts\Integration\DeviceIntegrationProviderInterface;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use Illuminate\Support\Facades\Log;

class NullDeviceIntegrationProvider implements DeviceIntegrationProviderInterface
{
    public function sendDeviceCommand(Device $device, DeviceCommand $command): bool
    {
        Log::info("NullDeviceIntegrationProvider: Queued command [{$command->command_id}] for physical device [{$device->device_id}] registered on backend integration boundary.");
        return true;
    }

    public function verifyDeviceConnectivity(Device $device): bool
    {
        return $device->is_online;
    }
}
