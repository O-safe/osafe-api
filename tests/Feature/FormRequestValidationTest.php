<?php

namespace Tests\Feature;

use App\Http\Requests\Device\DeviceCommandRequest;
use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Geofence\StoreGeofenceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_device_request_valid_payload_accepted(): void
    {
        $request = new StoreDeviceRequest();

        $validator = Validator::make([
            'serial_number' => 'SN-SAFE-9981',
            'imei' => '351756051523999',
            'model' => 'O SAFE Watch Pro',
            'connectivity' => 'cellular',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_store_device_request_required_fields_rejected(): void
    {
        $request = new StoreDeviceRequest();

        $validator = Validator::make([
            'model' => 'O SAFE Watch Pro',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('serial_number', $validator->errors()->messages());
    }

    public function test_invalid_coordinates_rejected_by_geofence_request(): void
    {
        $request = new StoreGeofenceRequest();

        // Invalid Latitude > 90 and Longitude < -180
        $validator = Validator::make([
            'name' => 'Invalid Bounds Zone',
            'center_latitude' => 95.1234,
            'center_longitude' => -185.4321,
            'radius_meters' => 100,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('center_latitude', $validator->errors()->messages());
        $this->assertArrayHasKey('center_longitude', $validator->errors()->messages());
    }

    public function test_invalid_enum_rejected_by_device_command_request(): void
    {
        $request = new DeviceCommandRequest();

        $validator = Validator::make([
            'command_type' => 'invalid_unsupported_command',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('command_type', $validator->errors()->messages());
    }
}
