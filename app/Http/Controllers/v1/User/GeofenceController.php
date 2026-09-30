<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Geofence\AttachDeviceGeofenceRequest;
use App\Http\Requests\Geofence\StoreGeofenceRequest;
use App\Http\Requests\Geofence\UpdateGeofenceRequest;
use App\Http\Resources\Geofence\GeofenceEventResource;
use App\Http\Resources\Geofence\GeofenceResource;
use App\Models\Device\Device;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\Geofence;
use App\Models\Geofence\GeofenceEvent;
use App\Services\Geofence\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $familyIds = FamilyMember::where('user_id', $user->user_id)->pluck('family_id');

        $geofences = Geofence::with(['devices', 'family'])
            ->where('owner_user_id', $user->user_id)
            ->when($familyIds->isNotEmpty(), fn($q) => $q->orWhereIn('family_id', $familyIds))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Geofences retrieved successfully.',
            'data' => GeofenceResource::collection($geofences),
        ], 200);
    }

    public function store(StoreGeofenceRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('create', Geofence::class);

        $geofence = $this->geofenceService->createGeofence($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Geofence created successfully.',
            'data' => new GeofenceResource($geofence->load(['devices', 'family'])),
        ], 201);
    }

    public function show(Request $request, Geofence $geofence): JsonResponse
    {
        $this->authorize('view', $geofence);

        return response()->json([
            'success' => true,
            'message' => 'Geofence details retrieved successfully.',
            'data' => new GeofenceResource($geofence->load(['devices', 'family'])),
        ], 200);
    }

    public function update(UpdateGeofenceRequest $request, Geofence $geofence): JsonResponse
    {
        $this->authorize('update', $geofence);

        $geofence->update(array_merge($request->validated(), [
            'updated_by' => $request->user()->user_id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Geofence updated successfully.',
            'data' => new GeofenceResource($geofence->load(['devices', 'family'])),
        ], 200);
    }

    public function destroy(Request $request, Geofence $geofence): JsonResponse
    {
        $this->authorize('delete', $geofence);

        $geofence->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Geofence deactivated successfully.',
        ], 200);
    }

    public function attachDevice(AttachDeviceGeofenceRequest $request, Geofence $geofence): JsonResponse
    {
        $actor = $request->user();
        $this->authorize('update', $geofence);

        $device = Device::findOrFail($request->device_id);

        $this->geofenceService->attachDevice($actor, $geofence, $device);

        return response()->json([
            'success' => true,
            'message' => 'Device attached to geofence successfully.',
            'data' => new GeofenceResource($geofence->fresh(['devices', 'family'])),
        ], 200);
    }

    public function detachDevice(Request $request, Geofence $geofence, Device $device): JsonResponse
    {
        $actor = $request->user();
        $this->authorize('update', $geofence);

        $this->geofenceService->detachDevice($actor, $geofence, $device);

        return response()->json([
            'success' => true,
            'message' => 'Device detached from geofence successfully.',
        ], 200);
    }

    public function events(Request $request, Geofence $geofence): JsonResponse
    {
        $this->authorize('view', $geofence);

        $events = GeofenceEvent::with('device')
            ->where('geofence_id', $geofence->geofence_id)
            ->latest('event_time')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Geofence events retrieved successfully.',
            'data' => GeofenceEventResource::collection($events->items()),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ], 200);
    }
}
