<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alert\NotificationPreferenceResource;
use App\Http\Resources\Alert\NotificationResource;
use App\Models\Notification\NotificationPreference;
use App\Models\Notification\OsafeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = OsafeNotification::where('user_id', $user->user_id)
            ->latest('sent_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => NotificationResource::collection($notifications->items()),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ], 200);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = OsafeNotification::where('notification_id', $id)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => new NotificationResource($notification),
        ], 200);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $prefs = NotificationPreference::where('user_id', $user->user_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences retrieved successfully.',
            'data' => NotificationPreferenceResource::collection($prefs),
        ], 200);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'channel' => 'required|string',
            'notification_type' => 'sometimes|required|string',
            'alert_type' => 'sometimes|required|string',
            'enabled' => 'sometimes|required|boolean',
            'is_enabled' => 'sometimes|required|boolean',
        ]);

        $type = $validated['notification_type'] ?? $validated['alert_type'] ?? 'alert';
        $enabled = $validated['enabled'] ?? $validated['is_enabled'] ?? true;

        $pref = NotificationPreference::updateOrCreate(
            [
                'user_id' => $user->user_id,
                'channel' => $validated['channel'],
                'notification_type' => $type,
            ],
            [
                'enabled' => $enabled,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preference updated successfully.',
            'data' => new NotificationPreferenceResource($pref),
        ], 200);
    }
}
