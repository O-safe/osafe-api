<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalApiKey
{
    /**
     * Handle an incoming request.
     *
     * Security fix: Previously this checked the incoming API key against
     * config('app.key') — the Laravel application encryption key. Using the
     * encryption key as an API key is a security concern because:
     *   1. The app key is used for payload encryption; sharing it widens its attack surface.
     *   2. Any error message or log that leaks the value would expose the encryption key.
     *
     * Changed to use a separate APP_API_KEY environment variable.
     * If APP_API_KEY is not set (e.g., local dev), the check is bypassed to avoid
     * breaking local development; set APP_API_KEY in production to enforce it.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validKey = config('app.api_key');

        // If no API key is configured (e.g., local dev without APP_API_KEY set),
        // skip the check to avoid breaking development environments.
        if (empty($validKey)) {
            return $next($request);
        }

        $providedKey = $request->header('X-API-KEY');

        if (!$providedKey || $providedKey !== $validKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API Key.'
            ], 401);
        }

        return $next($request);
    }
}
