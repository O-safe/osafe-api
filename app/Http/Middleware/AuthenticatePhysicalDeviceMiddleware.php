<?php

namespace App\Http\Middleware;

use App\Services\Integration\DeviceIntegrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePhysicalDeviceMiddleware
{
    public function __construct(
        protected DeviceIntegrationService $integrationService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token');

        if (!$token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (!$token) {
            return response()->json([
                'message' => 'Missing physical device authentication credentials.',
            ], 401);
        }

        $device = $this->integrationService->authenticateDevice($token);

        if (!$device) {
            return response()->json([
                'message' => 'Invalid or revoked device integration credentials.',
            ], 401);
        }

        $request->attributes->set('authenticated_device', $device);
        app()->instance('authenticated_device', $device);

        return $next($request);
    }
}
