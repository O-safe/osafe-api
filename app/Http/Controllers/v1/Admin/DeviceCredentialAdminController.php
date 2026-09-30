<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceIntegration\IssueDeviceCredentialsRequest;
use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeviceCredentialAdminController extends Controller
{
    public function __construct(
        protected DeviceIntegrationService $credentialService
    ) {}

    public function issue(IssueDeviceCredentialsRequest $request, Device $device): JsonResponse
    {
        Gate::authorize('update', $device);

        $platform = $request->validated()['platform'] ?? 'osafe_tracker';
        $result = $this->credentialService->issueCredentials($device, $platform, $request->user());

        return response()->json([
            'message' => 'Physical device credentials issued successfully.',
            'data' => $result,
        ], 201);
    }

    public function rotate(IssueDeviceCredentialsRequest $request, Device $device): JsonResponse
    {
        Gate::authorize('update', $device);

        $platform = $request->validated()['platform'] ?? 'osafe_tracker';
        $result = $this->credentialService->rotateCredentials($device, $platform, $request->user());

        return response()->json([
            'message' => 'Physical device credentials rotated successfully.',
            'data' => $result,
        ]);
    }

    public function revoke(IssueDeviceCredentialsRequest $request, Device $device): JsonResponse
    {
        Gate::authorize('update', $device);

        $platform = $request->validated()['platform'] ?? 'osafe_tracker';
        $revoked = $this->credentialService->revokeCredentials($device, $platform, $request->user());

        return response()->json([
            'message' => $revoked ? 'Physical device credentials revoked successfully.' : 'No active credentials found to revoke.',
            'revoked' => $revoked,
        ]);
    }
}
