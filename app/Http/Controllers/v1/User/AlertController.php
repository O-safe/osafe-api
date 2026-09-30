<?php

namespace App\Http\Controllers\v1\User;

use App\Enums\AlertStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Alert\AlertResource;
use App\Models\Notification\Alert;
use App\Services\Alert\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Alert::with(['device', 'geofence'])
            ->where('user_id', $user->user_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->latest('triggered_at')->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Alerts retrieved successfully.',
            'data' => AlertResource::collection($alerts->items()),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ], 200);
    }

    public function show(Request $request, Alert $alert): JsonResponse
    {
        $this->authorize('view', $alert);

        return response()->json([
            'success' => true,
            'message' => 'Alert details retrieved successfully.',
            'data' => new AlertResource($alert->load(['device', 'geofence'])),
        ], 200);
    }

    public function markRead(Request $request, Alert $alert): JsonResponse
    {
        $actor = $request->user();
        $this->authorize('view', $alert);

        $updated = $this->alertService->markAsRead($actor, $alert);

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as read.',
            'data' => new AlertResource($updated->load(['device', 'geofence'])),
        ], 200);
    }

    public function dismiss(Request $request, Alert $alert): JsonResponse
    {
        $actor = $request->user();
        $this->authorize('view', $alert);

        $alert->update([
            'status' => AlertStatus::Dismissed,
            'is_read' => true,
            'read_at' => $alert->read_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alert dismissed.',
            'data' => new AlertResource($alert->fresh(['device', 'geofence'])),
        ], 200);
    }

    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $actor = $request->user();
        $this->authorize('resolve', $alert);

        $updated = $this->alertService->resolveAlert($actor, $alert);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved.',
            'data' => new AlertResource($updated->load(['device', 'geofence'])),
        ], 200);
    }
}
