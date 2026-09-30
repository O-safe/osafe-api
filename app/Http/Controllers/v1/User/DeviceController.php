<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\DeviceCommandRequest;
use App\Http\Resources\Device\DeviceAssignmentResource;
use App\Http\Resources\Device\DeviceCommandResource;
use App\Http\Resources\Device\DeviceNetworkResource;
use App\Http\Resources\Device\DeviceResource;
use App\Http\Resources\Device\DeviceStatusHistoryResource;
use App\Models\Device\Device;
use App\Models\Family\FamilyMember;
use App\Services\Device\DeviceCommandAuthorizationService;
use App\Services\Device\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService,
        protected DeviceCommandAuthorizationService $commandAuthService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $familyIds = FamilyMember::where('user_id', $user->user_id)->pluck('family_id');

        $devices = Device::with(['latestLocation', 'assignments'])
            ->where('registered_by', $user->user_id)
            ->orWhereHas('assignments', function ($q) use ($user, $familyIds) {
                $q->where('status', 'active')
                    ->where(function ($sub) use ($user, $familyIds) {
                        $sub->where('user_id', $user->user_id);
                        if ($familyIds->isNotEmpty()) {
                            $sub->orWhereIn('family_id', $familyIds);
                        }
                    });
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Accessible devices retrieved successfully.',
            'data' => DeviceResource::collection($devices),
        ], 200);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        return response()->json([
            'success' => true,
            'message' => 'Device details retrieved successfully.',
            'data' => new DeviceResource($device->load(['latestLocation', 'assignments', 'networks'])),
        ], 200);
    }

    public function status(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $statusHistory = $device->statusHistories()->latest()->get();
        $networks = $device->networks()->get();

        return response()->json([
            'success' => true,
            'message' => 'Device status and network information retrieved successfully.',
            'data' => [
                'device_id' => $device->device_id,
                'status' => $device->status,
                'is_activated' => $device->is_activated,
                'battery_level' => $device->battery_level,
                'battery_status' => $device->battery_status,
                'is_online' => $device->is_online,
                'last_seen_at' => $device->last_seen_at,
                'status_history' => DeviceStatusHistoryResource::collection($statusHistory),
                'networks' => DeviceNetworkResource::collection($networks),
            ],
        ], 200);
    }

    public function assignment(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $assignment = $device->assignments()->where('status', 'active')->latest()->first();

        return response()->json([
            'success' => true,
            'message' => 'Device assignment details retrieved successfully.',
            'data' => $assignment ? new DeviceAssignmentResource($assignment->load(['user', 'family'])) : null,
        ], 200);
    }

    public function storeCommand(DeviceCommandRequest $request, Device $device): JsonResponse
    {
        $user = $request->user();

        $command = $this->commandAuthService->authorizeAndQueueCommand(
            actor: $user,
            device: $device,
            commandType: $request->command_type,
            payload: $request->payload ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Device command queued successfully.',
            'data' => new DeviceCommandResource($command),
        ], 201);
    }
}
