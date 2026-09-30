<?php

namespace App\Http\Controllers\v1\DeviceIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceIntegration\DeviceBatteryReportRequest;
use App\Http\Requests\DeviceIntegration\DeviceCommandAckRequest;
use App\Http\Requests\DeviceIntegration\DeviceHeartbeatRequest;
use App\Http\Requests\DeviceIntegration\DeviceLocationIngestionRequest;
use App\Http\Requests\DeviceIntegration\DeviceNetworkReportRequest;
use App\Http\Requests\DeviceIntegration\DeviceStatusReportRequest;
use App\Http\Resources\Device\DeviceCommandResource;
use App\Http\Resources\Device\DeviceNetworkResource;
use App\Http\Resources\Device\DeviceResource;
use App\Http\Resources\Location\DeviceLocationResource;
use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceIntegrationIngestionController extends Controller
{
    public function __construct(
        protected DeviceIntegrationIngestionService $ingestionService
    ) {}

    protected function getAuthenticatedDevice(Request $request): Device
    {
        return $request->get('authenticated_device') ?? app('authenticated_device');
    }

    public function heartbeat(DeviceHeartbeatRequest $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $updatedDevice = $this->ingestionService->recordHeartbeat(
            $device,
            $request->validated(),
            $request->ip()
        );

        return response()->json([
            'message' => 'Device heartbeat recorded successfully.',
            'data' => new DeviceResource($updatedDevice),
        ]);
    }

    public function status(DeviceStatusReportRequest $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $updatedDevice = $this->ingestionService->recordStatus(
            $device,
            $request->validated()['status'],
            $request->validated()['reason'] ?? null
        );

        return response()->json([
            'message' => 'Device status updated successfully.',
            'data' => new DeviceResource($updatedDevice),
        ]);
    }

    public function battery(DeviceBatteryReportRequest $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $updatedDevice = $this->ingestionService->recordBattery(
            $device,
            (float) $request->validated()['battery_level'],
            $request->validated()['battery_status'] ?? null
        );

        return response()->json([
            'message' => 'Device battery status updated successfully.',
            'data' => new DeviceResource($updatedDevice),
        ]);
    }

    public function network(DeviceNetworkReportRequest $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $networkRecord = $this->ingestionService->recordNetwork($device, $request->validated());

        return response()->json([
            'message' => 'Device network state recorded successfully.',
            'data' => new DeviceNetworkResource($networkRecord),
        ]);
    }

    public function location(DeviceLocationIngestionRequest $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $location = $this->ingestionService->ingestLocation($device, $request->validated());

        return response()->json([
            'message' => 'Device location ingested successfully.',
            'data' => new DeviceLocationResource($location),
        ], 201);
    }

    public function commands(Request $request): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $pendingCommands = $this->ingestionService->getPendingCommands($device);

        return response()->json([
            'message' => 'Pending device commands retrieved successfully.',
            'data' => DeviceCommandResource::collection($pendingCommands),
        ]);
    }

    public function ackCommand(DeviceCommandAckRequest $request, int $commandId): JsonResponse
    {
        $device = $this->getAuthenticatedDevice($request);
        $validated = $request->validated();

        $updatedCommand = $this->ingestionService->acknowledgeCommand(
            $device,
            $commandId,
            $validated['status'],
            $validated['response_payload'] ?? [],
            $validated['error_message'] ?? null
        );

        return response()->json([
            'message' => 'Device command acknowledgement processed successfully.',
            'data' => new DeviceCommandResource($updatedCommand),
        ]);
    }
}
