<?php

namespace App\Http\Middleware;

use App\Models\Admin\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustDeviceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Verifies that:
     *   1. A Device-ID header is present.
     *   2. The device is trusted (verified_at is set).
     *   3. The trusted device belongs to the authenticated user (user_id match).
     *      Without check 3, any user knowing another user's device_id could bypass this
     *      middleware — a cross-account authorization vulnerability.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Device-ID');
        $token    = $request->user()?->currentAccessToken();

        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID is required.'
            ], 403);
        }

        // Security fix: scope device lookup to both device_id AND the authenticated user_id.
        // Previously this only filtered by device_id, allowing cross-account device reuse.
        $authenticatedUserId = $request->user()?->getAuthIdentifier();

        $device = UserDevice::where('device_id', $deviceId)
            ->where('user_id', $authenticatedUserId)
            ->whereNotNull('verified_at')
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device is not trusted.'
            ], 403);
        }

        if ($token && $token->device_id !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token not valid for this device.'
            ], 401);
        }

        return $next($request);
    }
}
