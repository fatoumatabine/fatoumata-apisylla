<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () { // Réactiver le middleware 'auth:sanctum'
    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes non archivés",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page (default: 1)",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (default: 10, max: 100)",
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type (epargne, cheque)",
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (actif, bloque, ferme)",
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri (dateCreation, solde, titulaire)",
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre (asc, desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des comptes récupérée avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteResource")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=3),
     *                 @OA\Property(property="totalItems", type="integer", example=25),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                 @OA\Property(property="hasNext", type="boolean", example=true),
     *                 @OA\Property(property="hasPrevious", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de requête",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de requête"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    Route::get('/comptes', [CompteController::class, 'index']);

    /**
     * @OA\Get(
     *     path="/comptes/archived",
     *     summary="Lister tous les comptes archivés",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page (default: 1)",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (default: 10, max: 100)",
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri (dateCreation, solde, titulaire)",
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre (asc, desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des comptes archivés récupérée avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteResource")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=3),
     *                 @OA\Property(property="totalItems", type="integer", example=25),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                 @OA\Property(property="hasNext", type="boolean", example=true),
     *                 @OA\Property(property="hasPrevious", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="self", type="string", example="/api/v1/comptes/archived?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/api/v1/comptes/archived?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/api/v1/comptes/archived?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/api/v1/comptes/archived?page=3&limit=10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de requête",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de requête"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    Route::get('/comptes/archived', [CompteController::class, 'archived']);
});
