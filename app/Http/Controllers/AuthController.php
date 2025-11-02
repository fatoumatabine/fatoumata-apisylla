<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\ApiResponseTrait;
use Laravel\Passport\Passport;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Représentation d'un utilisateur",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin User"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "client"}, example="admin"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
    * @OA\Post(
    *     path="/api/v1/auth/login",
    *     operationId="login",
    *     tags={"Authentification"},
    *     summary="Connexion utilisateur",
    *     description="Authentifie un utilisateur (admin ou client) et retourne un access token et refresh token.",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"email","password"},
    *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
    *             @OA\Property(property="password", type="string", format="password", example="password")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Connexion réussie",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Login successful"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="user", ref="#/components/schemas/User"),
    *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
    *                 @OA\Property(property="refresh_token", type="string", example="refresh_token_here"),
    *                 @OA\Property(property="token_type", type="string", example="Bearer"),
    *                 @OA\Property(property="expires_in", type="integer", example=3600)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Identifiants invalides",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Invalid credentials"),
    *             @OA\Property(property="error", type="object",
    *                 @OA\Property(property="code", type="string", example="INVALID_CREDENTIALS")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Erreur de validation",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Validation failed"),
    *             @OA\Property(property="errors", type="object")
    *         )
    *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401, 'INVALID_CREDENTIALS');
        }

        // Create access token (Sanctum)
        $token = $user->createToken('API Token');

        return $this->success([
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
    * @OA\Post(
    *     path="/api/v1/auth/refresh",
    *     operationId="refresh",
    *     tags={"Authentification"},
    *     summary="Renouveler le token d'accès",
    *     description="Utilise un refresh token pour obtenir un nouveau token d'accès.",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"refresh_token"},
    *             @OA\Property(property="refresh_token", type="string", example="refresh_token_here")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Token renouvelé avec succès",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
    *                 @OA\Property(property="refresh_token", type="string", example="new_refresh_token_here"),
    *                 @OA\Property(property="token_type", type="string", example="Bearer"),
    *                 @OA\Property(property="expires_in", type="integer", example=3600)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Refresh token invalide",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Invalid refresh token"),
    *             @OA\Property(property="error", type="object",
    *                 @OA\Property(property="code", type="string", example="INVALID_REFRESH_TOKEN")
    *             )
    *         )
    *     )
    * )
    */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // For Passport personal access tokens, we implement a simple refresh mechanism
        // In a full OAuth2 implementation, this would use the refresh token grant
        try {
            $refreshToken = \Laravel\Passport\RefreshToken::where('id', $request->refresh_token)->first();

            if (!$refreshToken || $refreshToken->revoked || $refreshToken->expires_at->isPast()) {
                return $this->error('Invalid refresh token', 401, 'INVALID_REFRESH_TOKEN');
            }

            $accessToken = $refreshToken->accessToken;

            if (!$accessToken || $accessToken->revoked || $accessToken->expires_at->isPast()) {
                return $this->error('Invalid refresh token', 401, 'INVALID_REFRESH_TOKEN');
            }

            // Revoke old token
            $accessToken->revoke();

            // Create new token
            $newToken = $accessToken->user->createToken('API Token');

            return $this->success([
                'access_token' => $newToken->accessToken,
                'refresh_token' => $newToken->refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 hour
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->error('Invalid refresh token', 401, 'INVALID_REFRESH_TOKEN');
        }
    }

    /**
    * @OA\Post(
    *     path="/api/v1/auth/logout",
    *     operationId="logout",
    *     tags={"Authentification"},
    *     summary="Déconnexion utilisateur",
    *     description="Invalide le token d'accès actuel.",
    *     security={{"bearerAuth": {}}},
    *     @OA\Response(
    *         response=200,
    *         description="Déconnexion réussie",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Logged out successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non authentifié",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Unauthenticated")
    *         )
    *     )
    * )
    */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->success(null, 'Logged out successfully');
    }
}
