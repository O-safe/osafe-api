<?php

namespace App\Http\Controllers\v1\Admin;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\AssignDeviceRequest;
use App\Http\Requests\Device\ReassignDeviceRequest;
use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Http\Resources\Device\DeviceAssignmentResource;
use App\Http\Resources\Device\DeviceResource;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\User\User;
use App\Services\Device\DeviceAssignmentService;
use App\Services\Device\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceManagementController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService,
        protected DeviceAssignmentService $assignmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Device::class);

        $query = Device::with(['latestLocation', 'assignments.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('imei', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        $devices = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Admin devices retrieved successfully.',
            'data' => DeviceResource::collection($devices->items()),
            'pagination' => [
                'current_page' => $devices->currentPage(),
                'last_page' => $devices->lastPage(),
                'per_page' => $devices->perPage(),
                'total' => $devices->total(),
            ],
        ], 200);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        return response()->json([
            'success' => true,
            'message' => 'Device details retrieved successfully.',
            'data' => new DeviceResource($device->load(['latestLocation', 'assignments.user', 'assignments.family', 'networks'])),
        ], 200);
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('create', Device::class);

        $device = Device::create(array_merge($request->validated(), [
            'created_by' => $staff->staff_id,
            'status' => DeviceStatus::Unactivated,
            'is_activated' => false,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully by admin.',
            'data' => new DeviceResource($device),
        ], 201);
    }

    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);

        $device->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Device metadata updated successfully.',
            'data' => new DeviceResource($device->fresh()),
        ], 200);
    }

    public function activate(Request $request, Device $device): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $device);

        $activated = $this->deviceService->activateDevice($staff, $device);

        return response()->json([
            'success' => true,
            'message' => 'Device activated successfully.',
            'data' => new DeviceResource($activated),
        ], 200);
    }

    public function deactivate(Request $request, Device $device): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $device);

        $device->update([
            'status' => DeviceStatus::Inactive,
            'is_activated' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device suspended/deactivated successfully.',
            'data' => new DeviceResource($device->fresh()),
        ], 200);
    }

    public function assign(AssignDeviceRequest $request, Device $device): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $device);

        if ($request->filled('user_id')) {
            $user = User::findOrFail($request->user_id);
            $assignment = $this->assignmentService->assignToUser($staff, $device, $user, $request->notes);
        } else {
            $family = Family::findOrFail($request->family_id);
            $assignment = $this->assignmentService->assignToFamily($staff, $device, $family, $request->notes);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device assigned successfully.',
            'data' => new DeviceAssignmentResource($assignment->load(['user', 'family'])),
        ], 201);
    }

    public function reassign(ReassignDeviceRequest $request, Device $device): JsonResponse
    {
        return $this->assign($request, $device);
    }

    public function revokeAssignment(Request $request, Device $device): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $device);

        $assignment = DeviceAssignment::where('device_id', $device->device_id)
            ->where('status', 'active')
            ->firstOrFail();

        $this->assignmentService->revokeAssignment($staff, $assignment, $request->reason ?? 'Revoked by administrator.');

        return response()->json([
            'success' => true,
            'message' => 'Device assignment revoked successfully.',
        ], 200);
    }
}
