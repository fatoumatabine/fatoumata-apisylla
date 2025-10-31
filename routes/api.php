<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;

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

Route::prefix('v1')->group(function () {

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     operationId="login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur (admin ou client) et retourne un token Bearer.",
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
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
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
    Route::post('/login', [AuthController::class, 'login']);

    // Routes protégées
    Route::middleware('auth:api')->group(function () {
    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Lister tous les comptes non archivés",
     *     tags={"Comptes"},
     // *     security={{"bearerAuth": {}}},
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
     *         name="numeroCompte",
     *         in="query",
     *         description="Filtrer par numéro de compte exact",
     *         @OA\Schema(type="string")
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
     *         description="Tri (date_creation, solde, titulaire)",
     *         @OA\Schema(type="string", enum={"date_creation", "solde", "titulaire"}, default="date_creation")
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
    * @OA\Post(
    *     path="/api/v1/comptes",
    *     summary="Créer un nouveau compte",
    *     tags={"Comptes"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"type", "solde", "devise", "client"},
    *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
    *             @OA\Property(property="soldeInitial", type="number", example=500000),
    *             @OA\Property(property="devise", type="string", example="FCFA"),
    *             @OA\Property(property="solde", type="number", minimum=10000, example=500000),
    *             @OA\Property(property="client", type="object",
    *                 required={"titulaire", "nci", "email", "telephone", "adresse"},
    *                 @OA\Property(property="id", type="integer", nullable=true, example=null),
    *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
    *                 @OA\Property(property="nci", type="string", example="1234567890123"),
    *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
    *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
    *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="Compte créé avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
    *             @OA\Property(property="data", ref="#/components/schemas/CompteResource")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Données invalides",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="error", type="object",
    *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
    *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
    *                 @OA\Property(property="details", type="object")
    *             )
    *         )
    *     )
    * )
    */
    Route::post('/comptes', [CompteController::class, 'store'])->middleware('logging');

    /**
    * @OA\Get(
    *     path="/api/v1/comptes/archived",
    *     summary="Lister tous les comptes archivés",
    *     tags={"Comptes"},
    // *     security={{"bearerAuth": {}}},
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
      *         description="Tri (date_creation, solde, titulaire)",
      *         @OA\Schema(type="string", enum={"date_creation", "solde", "titulaire"}, default="date_creation")
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
    Route::patch('/comptes/{id}/archive', [CompteController::class, 'archive']);
    Route::patch('/comptes/{id}/unarchive', [CompteController::class, 'unarchive']);
    Route::get('/comptes/archived', [CompteController::class, 'archived']);
    Route::get('/comptes/{id}', [CompteController::class, 'show']);
    Route::get('/comptes/numero/{numero}', [CompteController::class, 'showByNumero']);
    Route::delete('/comptes/{id}', [CompteController::class, 'destroy']);
    Route::patch('/comptes/{id}/block', [CompteController::class, 'block']);
    Route::patch('/comptes/{id}/unblock', [CompteController::class, 'unblock']);
    /**
     * @OA\Patch(
     *      path="/api/v1/comptes/{compteId}",
     *      operationId="updateCompte",
     *      tags={"Comptes"},
     *      summary="Modifier les informations d'un compte",
     *      description="Met à jour les informations d'un compte et de son client associé. Tous les champs sont optionnels, mais au moins un champ de modification doit être fourni.",
     // *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="compteId",
     *          in="path",
     *          required=true,
     *          description="ID du compte à modifier",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Champs à modifier (au moins un requis)",
     *          @OA\JsonContent(
     *              @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior", description="Nouveau nom du titulaire (optionnel)"),
     *              @OA\Property(property="informationsClient", type="object",
     *                  @OA\Property(property="telephone", type="string", example="+221771234568", description="Nouveau numéro de téléphone (optionnel)"),
     *                  @OA\Property(property="email", type="string", format="email", example="amadou.diallo.jr@example.com", description="Nouvel email (optionnel)"),
     *                  @OA\Property(property="password", type="string", format="password", example="NewSecurePassword123!", description="Nouveau mot de passe (optionnel)")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte mis à jour avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Données invalides ou aucun champ de modification fourni",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="error", type="object",
     *                  @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                  @OA\Property(property="message", type="string", example="Au moins un champ de modification est requis."),
     *                  @OA\Property(property="details", type="object")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="error", type="object",
     *                  @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                  @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *              )
     *          )
     *      )
     * )
     */
    Route::patch('/comptes/{compteId}', [CompteController::class, 'update']); // Temporairement désactivé le middleware 'logging' pour le test

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}/transactions",
     *     summary="Lister les transactions d'un compte",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type (credit, debit)",
     *         @OA\Schema(type="string", enum={"credit", "debit"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut (pending, completed, failed)",
     *         @OA\Schema(type="string", enum={"pending", "completed", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre de transactions par page",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="include_archived",
     *         in="query",
     *         description="Inclure les transactions archivées (true/false)",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionResource"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */
     Route::get('/comptes/{compteId}/transactions', [TransactionController::class, 'index']);
      Route::get('/comptes/{compteId}/transactions/stats', [TransactionController::class, 'stats']);
      Route::post('/comptes/{compteId}/transactions', [TransactionController::class, 'store']);
      Route::get('/comptes/{compteId}/transactions/{transactionId}', [TransactionController::class, 'show']);

      Route::get('/clients/telephone/{telephone}', [ClientController::class, 'showByTelephone']);

      Route::get('/dashboard', [DashboardController::class, 'index']);

    }); // Fin du groupe protégé

     /**
     * @OA\Post(
     *     path="/api/v1/comptes/{compteId}/transactions",
     *     summary="Créer une nouvelle transaction",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "montant"},
     *             @OA\Property(property="type", type="string", enum={"credit", "debit"}, example="credit"),
     *             @OA\Property(property="montant", type="number", format="float", example=10000),
     *             @OA\Property(property="description", type="string", nullable=true, example="Dépôt initial")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction créée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/TransactionResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */
     Route::post('/comptes/{compteId}/transactions', [TransactionController::class, 'store']);

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}/transactions/{transactionId}",
     *     summary="Afficher une transaction spécifique",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="transactionId",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction trouvée"),
     *             @OA\Property(property="data", ref="#/components/schemas/TransactionResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */
     Route::get('/comptes/{compteId}/transactions/{transactionId}', [TransactionController::class, 'show']);
 });
