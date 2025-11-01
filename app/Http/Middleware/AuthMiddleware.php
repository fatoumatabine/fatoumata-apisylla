<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Vérifie si l'utilisateur est authentifié via Passport.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Token d\'authentification manquant ou invalide',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        return $next($request);
    }
}