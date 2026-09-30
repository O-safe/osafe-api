<?php

namespace App\Contracts\Integration;

use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;

interface DeviceIntegrationProviderInterface
{
    public function sendDeviceCommand(Device $device, DeviceCommand $command): bool;

    public function verifyDeviceConnectivity(Device $device): bool;
}
