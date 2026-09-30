<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\ReportResource;
use App\Models\System\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Available report types retrieved successfully.',
            'data' => [
                ['type' => 'device_activity', 'name' => 'Device Activity Report', 'description' => 'Summary of device online status and command execution.'],
                ['type' => 'geofence_violations', 'name' => 'Geofence Violation Summary', 'description' => 'Summary of geofence entry and exit alerts.'],
                ['type' => 'user_subscription_audit', 'name' => 'Subscription Audit Report', 'description' => 'Active plan allocations and revenue overview.'],
                ['type' => 'system_health', 'name' => 'System Health Audit', 'description' => 'System error rate and queue processing statistics.'],
            ],
        ], 200);
    }

    public function generate(Request $request): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('generate', Report::class);

        $validated = $request->validate([
            'report_type' => 'required|string',
            'parameters' => 'nullable|array',
        ]);

        $report = Report::create([
            'report_type' => $validated['report_type'],
            'generated_by' => $staff->staff_id,
            'generated_by_type' => 'staff',
            'status' => 'completed',
            'parameters' => $validated['parameters'] ?? [],
            'result_data' => [
                'summary' => 'Report generated successfully.',
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully.',
            'data' => new ReportResource($report),
        ], 201);
    }

    public function show(Report $report): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Report details retrieved successfully.',
            'data' => new ReportResource($report),
        ], 200);
    }
}
