<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alert\AlertResource;
use App\Http\Resources\Subscription\UserSubscriptionResource;
use App\Models\Notification\Alert;
use App\Models\Subscription\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeSubscription = UserSubscription::with('plan')
            ->where('user_id', $user->user_id)
            ->where('status', 'active')
            ->latest('start_date')
            ->first();

        $assignedDevicesCount = $user->assignedDevices()
            ->wherePivot('status', 'active')
            ->count();

        $familyMembersCount = $user->familyMemberships()
            ->where('status', 'active')
            ->count();

        $unreadAlertsCount = Alert::where('user_id', $user->user_id)
            ->whereNull('read_at')
            ->count();

        $recentAlerts = Alert::with(['device', 'geofence'])
            ->where('user_id', $user->user_id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User dashboard KPI metrics fetched successfully.',
            'data' => [
                'active_subscription' => $activeSubscription ? new UserSubscriptionResource($activeSubscription) : null,
                'assigned_devices_count' => $assignedDevicesCount,
                'family_members_count' => $familyMembersCount,
                'unread_alerts_count' => $unreadAlertsCount,
                'recent_alerts' => AlertResource::collection($recentAlerts),
            ],
        ], 200);
    }
}
