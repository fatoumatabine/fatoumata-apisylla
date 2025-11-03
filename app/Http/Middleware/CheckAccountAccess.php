<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Compte;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $accountId = null): Response
    {
        $user = $request->user('api');

        // Si pas d'utilisateur authentifié, refuser l'accès
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise',
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'timestamp' => now()->toISOString(),
                    'path' => $request->path(),
                    'traceId' => uniqid()
                ]
            ], 401);
        }

        // Si un ID de compte est spécifié, vérifier l'accès à ce compte
        if ($accountId) {
            $compte = Compte::find($accountId);

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non trouvé',
                    'error' => [
                        'code' => 'COMPTE_NOT_FOUND',
                        'timestamp' => now()->toISOString(),
                        'path' => $request->path(),
                        'traceId' => uniqid()
                    ]
                ], 404);
            }

            // Vérifier l'autorisation d'accès au compte
            if (!$this->canAccessAccount($compte, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à ce compte',
                    'error' => [
                        'code' => 'ACCESS_DENIED',
                        'timestamp' => now()->toISOString(),
                        'path' => $request->path(),
                        'traceId' => uniqid()
                    ]
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un compte
     */
    private function canAccessAccount(Compte $compte, $user): bool
    {
        // Les admins peuvent accéder à tous les comptes
        if ($user->isAdmin()) {
            return true;
        }

        // Les clients ne peuvent accéder qu'à leurs propres comptes
        if ($user->isClient()) {
            return $compte->client && $compte->client->user_id === $user->id;
        }

        return false;
    }
}
