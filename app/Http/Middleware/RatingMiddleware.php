<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'rate_limit:' . $ip;
        $maxAttempts = 5; // Max 5 requests
        $decayMinutes = 1; // Per minute

        if (Cache::has($key)) {
            $attempts = Cache::get($key);
            if ($attempts >= $maxAttempts) {
                // Log the user who reached the limit (for demonstration)
                \Illuminate\Support\Facades\Log::warning("Rate limit exceeded for IP: {$ip}");
                return new JsonResponse(['success' => false, 'message' => 'Too Many Requests.', 'errors' => []], 429);
            }
            Cache::increment($key);
        } else {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
        }

        return $next($request);
    }
}
