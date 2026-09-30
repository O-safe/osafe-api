<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Location\DeviceLocationResource;
use App\Http\Resources\Location\LocationEventResource;
use App\Models\Device\Device;
use App\Models\Location\DeviceLocation;
use App\Models\Location\LocationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function current(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $location = $device->latestLocation;

        return response()->json([
            'success' => true,
            'message' => 'Current device location retrieved successfully.',
            'data' => $location ? new DeviceLocationResource($location) : null,
        ], 200);
    }

    public function history(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $query = DeviceLocation::where('device_id', $device->device_id)->latest('recorded_at');

        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->end_date);
        }

        $locations = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Device location history retrieved successfully.',
            'data' => DeviceLocationResource::collection($locations->items()),
            'pagination' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
            ],
        ], 200);
    }

    public function events(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $events = LocationEvent::where('device_id', $device->device_id)
            ->latest('event_time')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Location event history retrieved successfully.',
            'data' => LocationEventResource::collection($events->items()),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ], 200);
    }
}
