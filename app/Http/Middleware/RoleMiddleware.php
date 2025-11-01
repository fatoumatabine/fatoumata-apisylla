<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur a les permissions requises pour accéder à la ressource.
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Vérifier si l'utilisateur a les scopes requis
        $token = $request->user('api')->token();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'error' => 'INVALID_TOKEN'
            ], 401);
        }

        // Vérifier les scopes
        foreach ($scopes as $scope) {
            if (!$token->can($scope)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permissions insuffisantes pour cette opération',
                    'error' => 'INSUFFICIENT_PERMISSIONS',
                    'required_scope' => $scope
                ], 403);
            }
        }

        return $next($request);
    }
}