<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
    $response = $next($request);

        // Logger les opérations de création
        if ($request->isMethod('post') && $request->is('api/v1/comptes')) {
            \Illuminate\Support\Facades\Log::info('Opération de création', [
                'date' => now()->toDateString(),
                'heure' => now()->toTimeString(),
                'host' => $request->getHost(),
                'operation' => 'Création de compte',
                'ressource' => 'comptes',
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
