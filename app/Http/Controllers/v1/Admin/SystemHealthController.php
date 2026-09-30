<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\SystemHealthCheckResource;
use App\Http\Resources\System\SystemIncidentResource;
use App\Models\System\SystemHealthCheck;
use App\Models\System\SystemIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $dbStatus = 'healthy';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'unhealthy';
        }

        $cacheStatus = 'healthy';
        try {
            Redis::connection()->ping();
        } catch (\Throwable $e) {
            $cacheStatus = 'degraded';
        }

        $openIncidentsCount = SystemIncident::where('status', 'open')->count();

        return response()->json([
            'success' => true,
            'message' => 'System health status retrieved successfully.',
            'data' => [
                'application' => 'operational',
                'database' => $dbStatus,
                'cache' => $cacheStatus,
                'queue' => 'operational',
                'open_incidents_count' => $openIncidentsCount,
                'checked_at' => now()->toIso8601String(),
            ],
        ], 200);
    }

    public function checks(Request $request): JsonResponse
    {
        $checks = SystemHealthCheck::latest('checked_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'System health checks retrieved successfully.',
            'data' => SystemHealthCheckResource::collection($checks->items()),
            'pagination' => [
                'current_page' => $checks->currentPage(),
                'last_page' => $checks->lastPage(),
                'per_page' => $checks->perPage(),
                'total' => $checks->total(),
            ],
        ], 200);
    }

    public function incidents(Request $request): JsonResponse
    {
        $incidents = SystemIncident::latest('reported_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'System incidents retrieved successfully.',
            'data' => SystemIncidentResource::collection($incidents->items()),
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'per_page' => $incidents->perPage(),
                'total' => $incidents->total(),
            ],
        ], 200);
    }
}
