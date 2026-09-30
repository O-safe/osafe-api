<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alert\AlertResource;
use App\Http\Resources\System\AuditLogResource;
use App\Models\Device\Device;
use App\Models\Family\Family;
use App\Models\Notification\Alert;
use App\Models\Subscription\UserSubscription;
use App\Models\System\AuditLog;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();

        $totalUsers = User::count();
        $activeUsers = User::where('status_id', 1)->count();

        $totalDevices = Device::count();
        $activeDevices = Device::where('is_activated', true)->count();
        $unactivatedDevices = Device::where('is_activated', false)->count();

        $totalFamilies = Family::count();

        $activeSubscriptions = UserSubscription::where('status', 'active')->count();

        $recentAlerts = Alert::with(['device', 'user'])
            ->latest('triggered_at')
            ->take(5)
            ->get();

        $recentActivity = AuditLog::latest('created_at')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard KPIs fetched successfully.',
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                ],
                'devices' => [
                    'total' => $totalDevices,
                    'active' => $activeDevices,
                    'unactivated' => $unactivatedDevices,
                ],
                'families' => [
                    'total' => $totalFamilies,
                ],
                'subscriptions' => [
                    'active' => $activeSubscriptions,
                ],
                'recent_alerts' => AlertResource::collection($recentAlerts),
                'recent_activity' => AuditLogResource::collection($recentActivity),
            ],
        ], 200);
    }
}
