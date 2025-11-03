<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Resources\CompteResource;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UpdateCompteRequest;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API Comptes - Système de Gestion Bancaire",
 *      description="API REST pour la gestion des comptes bancaires avec authentification et validation avancée",
 *      @OA\Contact(
 *          email="fatoumata.sylla@example.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Serveur API"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Authentification Bearer Token pour accéder aux endpoints protégés",
 *     name="bearerAuth",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * Vérifier si l'utilisateur authentifié peut accéder à un compte spécifique
     */
    private function canAccessAccount(Compte $compte, $user): bool
    {
        // Les admins peuvent accéder à tous les comptes
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Les clients ne peuvent accéder qu'à leurs propres comptes
        if ($user && $user->isClient()) {
            return $compte->client && $compte->client->user_id === $user->id;
        }

        return false;
    }

    /**
     * @OA\Get(
     *      path="/api/v1/comptes",
     *      operationId="getComptesList",
     *      tags={"Comptes"},
     *      summary="Obtenir la liste des comptes",
     *      description="Retourne la liste des comptes avec des options de filtrage, recherche et pagination.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="type",
     *          in="query",
     *          description="Filtrer par type de compte (e.g., 'courant', 'epargne')",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="statut",
     *          in="query",
     *          description="Filtrer par statut de compte (e.g., 'actif', 'bloque', 'ferme')",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *          description="Rechercher par titulaire ou numéro de compte",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Champ de tri (e.g., 'dateCreation', 'solde')",
     *          @OA\Schema(type="string", default="dateCreation")
     *      ),
     *      @OA\Parameter(
     *          name="order",
     *          in="query",
     *          description="Ordre de tri ('asc' ou 'desc')",
     *          @OA\Schema(type="string", default="desc")
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Nombre d'éléments par page",
     *          @OA\Schema(type="integer", default=10)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Liste des comptes récupérée avec succès"),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteResource")),
     *              @OA\Property(property="pagination", type="object"),
     *              @OA\Property(property="links", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user('api');
        $query = Compte::query();

        // Autorisation : filtrer selon le rôle de l'utilisateur
        if ($user && $user->isClient()) {
            // Les clients ne voient que leurs propres comptes
            $query->whereHas('client', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        // Les admins voient tous les comptes (pas de filtre supplémentaire)

        // Filtrer par type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filtrer par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        // Recherche par titulaire ou numéro
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('titulaire', 'like', '%' . $searchTerm . '%')
                  ->orWhere('numeroCompte', 'like', '%' . $searchTerm . '%');
            });
        }

        // Tri
        $sortField = $request->input('sort', 'dateCreation');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $limit = $request->input('limit', 10);
        $limit = min($limit, 100); // Max 100 items per page
        $comptes = $query->with('client')->paginate($limit);

        return $this->paginate(CompteResource::collection($comptes), 'Liste des comptes récupérée avec succès');
    }

    /**
     * @OA\Post(
     *      path="/api/v1/comptes",
     *      operationId="storeCompte",
     *      tags={"Comptes"},
     *      summary="Créer un nouveau compte",
     *      description="Crée un nouveau compte bancaire, avec la possibilité de créer un nouveau client si nécessaire.",
     *      security={{"bearerAuth": {}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Données du compte à créer",
     *          @OA\JsonContent(
     *              required={"type","solde","devise","client"},
     *              @OA\Property(property="type", type="string", example="courant", description="Type de compte (e.g., 'courant', 'epargne')"),
     *              @OA\Property(property="solde", type="number", format="float", example=1000.00, description="Solde initial du compte"),
     *              @OA\Property(property="devise", type="string", example="XOF", description="Devise du compte (e.g., 'XOF', 'EUR', 'USD')"),
     *              @OA\Property(property="client", type="object",
     *                  @OA\Property(property="id", type="integer", example=1, description="ID du client existant (optionnel)"),
     *                  @OA\Property(property="titulaire", type="string", example="John Doe", description="Nom du titulaire (requis si nouveau client)"),
     *                  @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro de carte d'identité (requis si nouveau client)"),
     *                  @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email du client (requis si nouveau client)"),
     *                  @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone du client (requis si nouveau client)"),
     *                  @OA\Property(property="adresse", type="string", example="123 Rue Principale", description="Adresse du client (requis si nouveau client)")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Compte créé avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Erreur de validation",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(property="errors", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Erreur interne du serveur",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Erreur interne du serveur lors de la création du compte.")
     *          )
     *      )
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            $data = $request->validated();

            // Vérifier si le client existe
            $client = null;

            $password = null;
            $code = null;

            if (isset($data['client']['id'])) {
                $clientId = $data['client']['id'];
                Log::info('Tentative de trouver un client existant par ID: ' . $clientId);
                $client = \App\Models\Client::find($clientId);
                if (!$client) {
                    Log::warning('Client non trouvé pour l\'ID: ' . $clientId);
                    return $this->error('Client non trouvé.', 404, 'CLIENT_NOT_FOUND');
                }
                Log::info('Client existant trouvé par ID: ' . $client->id);
            } else {
                Log::info('client.id non fourni. Tentative de créer un nouveau client.');
                $password = \Illuminate\Support\Str::random(8);
                $code = \Illuminate\Support\Str::random(6);

                $client = \App\Models\Client::create([
                    'titulaire' => $data['client']['titulaire'],
                    'nci' => $data['client']['nci'],
                    'email' => $data['client']['email'],
                    'telephone' => $data['client']['telephone'],
                    'adresse' => $data['client']['adresse'],
                    'password' => $password, // Le hachage est géré par le modèle Client
                    'code' => $code, // Le hachage est géré par le modèle Client
                ]);
                Log::info('Nouveau client créé avec ID: ' . $client->id);
            }

            // Générer numéro de compte unique
            do {
                $numeroCompte = 'C' . str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Compte::where('numeroCompte', $numeroCompte)->exists());

            // Créer le compte
            $compte = Compte::create([
                'numeroCompte' => $numeroCompte,
                'titulaire' => $client->titulaire,
                'type' => $data['type'],
                'solde' => $data['solde'], // Correction: utiliser 'solde' au lieu de 'soldeInitial'
                'devise' => $data['devise'],
                'statut' => 'actif',
                'dateCreation' => now(),
                'client_id' => $client->id,
            ]);

            // Envoyer email et SMS (via event/listener)
            \Illuminate\Support\Facades\Event::dispatch(new \App\Events\ClientCreated($client, $password, $code));

            return $this->success(new CompteResource($compte), 'Compte créé avec succès', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed during account creation: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return $this->error('Validation failed', 422, 'VALIDATION_ERROR', $e->errors(), $request->fullUrl());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return $this->error('Erreur interne du serveur lors de la création du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()], $request->fullUrl(), (string) \Illuminate\Support\Str::uuid());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/comptes/archived",
     *      operationId="getArchivedComptesList",
     *      tags={"Comptes"},
     *      summary="Obtenir la liste des comptes archivés",
     *      description="Retourne la liste des comptes archivés avec des options de recherche et pagination.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *          description="Rechercher par titulaire ou numéro de compte",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Champ de tri (e.g., 'dateCreation', 'solde')",
     *          @OA\Schema(type="string", default="dateCreation")
     *      ),
     *      @OA\Parameter(
     *          name="order",
     *          in="query",
     *          description="Ordre de tri ('asc' ou 'desc')",
     *          @OA\Schema(type="string", default="desc")
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Nombre d'éléments par page",
     *          @OA\Schema(type="integer", default=10)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Liste des comptes archivés récupérée avec succès"),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteResource")),
     *              @OA\Property(property="pagination", type="object"),
     *              @OA\Property(property="links", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
     public function archived(Request $request)
     {
     $query = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)->where('archived', true); // Récupérer uniquement les comptes archivés

         // Recherche par titulaire ou numéro
         if ($request->has('search')) {
             $searchTerm = $request->input('search');
             $query->where(function ($q) use ($searchTerm) {
                 $q->where('titulaire', 'like', '%' . $searchTerm . '%')
                   ->orWhere('numeroCompte', 'like', '%' . $searchTerm . '%');
             });
         }

         // Tri
         $sortField = $request->input('sort', 'dateCreation');
         $sortOrder = $request->input('order', 'desc');
         $query->orderBy($sortField, $sortOrder);

         // Pagination
         $limit = $request->input('limit', 10);
         $limit = min($limit, 100); // Max 100 items per page
         $comptes = $query->with('client')->paginate($limit);

         return $this->paginate(CompteResource::collection($comptes), 'Liste des comptes archivés récupérée avec succès');
     }

    /**
     * @OA\Get(
     *      path="/api/v1/comptes/{id}",
     *      operationId="getCompteById",
     *      tags={"Comptes"},
     *      summary="Obtenir un compte spécifique",
     *      description="Retourne un compte bancaire par son ID.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à récupérer",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function show(Request $request, string $id)
    {
        try {
            $compte = Compte::with('client')->find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            // Vérifier l'autorisation
            $user = $request->user('api');
            if (!$this->canAccessAccount($compte, $user)) {
                return $this->error('Accès non autorisé à ce compte.', 403, 'ACCESS_DENIED');
            }

            return $this->success(new CompteResource($compte), 'Compte récupéré avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors de la récupération du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour les informations d'un compte et de son client.
     *
     * @param  \App\Http\Requests\UpdateCompteRequest  $request
     * @param  string  $compteId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCompteRequest $request, string $compteId)
    {
        try {
            $compte = Compte::where('id', $compteId)->first();

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND', [], $request->fullUrl());
            }

            $data = $request->validated(); // Utilisation de la validation de la requête de formulaire

            // La validation "au moins un champ de modification est requis" est maintenant gérée dans UpdateCompteRequest

            // Mettre à jour le titulaire du compte
            if (isset($data['titulaire'])) {
                $compte->titulaire = $data['titulaire'];
            }

            // Mettre à jour les informations du client
            if (isset($data['informationsClient'])) {
                $client = $compte->client; // Supposons qu'un compte a toujours un client

                if ($client) {
                    if (isset($data['informationsClient']['telephone'])) {
                        $client->telephone = $data['informationsClient']['telephone'];
                    }
                    if (isset($data['informationsClient']['email'])) {
                        $client->email = $data['informationsClient']['email'];
                    }
                    if (isset($data['informationsClient']['password'])) {
                        $client->password = bcrypt($data['informationsClient']['password']);
                    }
                    $client->save();
                }
            }

            $compte->save();

            return $this->success(new CompteResource($compte), 'Compte mis à jour avec succès', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, 'VALIDATION_ERROR', $e->errors(), $request->fullUrl());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->error('Erreur interne du serveur lors de la mise à jour du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()], $request->fullUrl(), (string) \Illuminate\Support\Str::uuid());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/comptes/{id}",
     *      operationId="deleteCompte",
     *      tags={"Comptes"},
     *      summary="Supprimer un compte",
     *      description="Supprime (archive) un compte bancaire par son ID.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à supprimer",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte supprimé avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte supprimé avec succès.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $compte = Compte::find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            $compte->forceDelete(); // Supprime définitivement le compte

            return $this->success(null, 'Compte supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors de la suppression du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/comptes/{id}/block",
     *      operationId="blockCompte",
     *      tags={"Comptes"},
     *      summary="Bloquer un compte épargne",
     *      description="Bloque un compte épargne spécifique pour une durée donnée.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à bloquer",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Date de fin de blocage",
     *          @OA\JsonContent(
     *              required={"date_fin_blocage"},
     *              @OA\Property(property="date_fin_blocage", type="string", format="date-time", example="2025-12-31T23:59:59Z", description="Date et heure de fin du blocage")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte bloqué avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte bloqué avec succès."),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Erreur de validation ou compte non épargne",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Le compte n'est pas un compte épargne ou la date de fin de blocage est invalide.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function block(Request $request, string $id)
    {
        try {
            $compte = Compte::find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            if ($compte->type !== 'epargne') {
                return $this->error('Seuls les comptes épargne peuvent être bloqués.', 422, 'INVALID_ACCOUNT_TYPE');
            }

            $request->validate([
                'date_fin_blocage' => 'required|date|after:now',
            ]);

            $compte->statut = 'bloque';
            $compte->date_debut_blocage = now();
            $compte->date_fin_blocage = $request->input('date_fin_blocage');
            $compte->save();

            return $this->success(new CompteResource($compte), 'Compte bloqué avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, 'VALIDATION_ERROR', $e->errors(), $request->fullUrl());
        } catch (\Exception $e) {
            Log::error('Erreur lors du blocage du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors du blocage du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/comptes/{id}/unblock",
     *      operationId="unblockCompte",
     *      tags={"Comptes"},
     *      summary="Débloquer un compte épargne",
     *      description="Débloque un compte épargne spécifique.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à débloquer",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte débloqué avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte débloqué avec succès."),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Compte non bloqué ou non épargne",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Le compte n'est pas bloqué ou n'est pas un compte épargne.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function unblock(string $id)
    {
        try {
            $compte = Compte::find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            if ($compte->type !== 'epargne' || $compte->statut !== 'bloque') {
                return $this->error('Le compte n\'est pas bloqué ou n\'est pas un compte épargne.', 422, 'INVALID_ACCOUNT_STATE');
            }

            $compte->statut = 'actif';
            $compte->date_debut_blocage = null;
            $compte->date_fin_blocage = null;
            $compte->save();

            return $this->success(new CompteResource($compte), 'Compte débloqué avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du déblocage du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors du déblocage du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/comptes/{id}/archive",
     *      operationId="archiveCompte",
     *      tags={"Comptes"},
     *      summary="Archiver un compte",
     *      description="Archive un compte bancaire spécifique.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à archiver",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte archivé avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte archivé avec succès."),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function archive(string $id)
    {
        try {
            $compte = Compte::find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            $compte->archived = true;
            $compte->save();

            // Archiver toutes les transactions associées
            $compte->transactions()->update(['archived' => true]);

            return $this->success(new CompteResource($compte), 'Compte archivé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors de l\'archivage du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/comptes/{id}/unarchive",
     *      operationId="unarchiveCompte",
     *      tags={"Comptes"},
     *      summary="Désarchiver un compte",
     *      description="Désarchive un compte bancaire spécifique.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte à désarchiver",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Compte désarchivé avec succès",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Compte désarchivé avec succès."),
     *              @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function unarchive(string $id)
    {
        try {
            $compte = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)->find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            $compte->archived = false;
            $compte->save();

            // Désarchiver toutes les transactions associées
            $compte->transactions()->update(['archived' => false]);

            return $this->success(new CompteResource($compte), 'Compte désarchivé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du désarchivage du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors du désarchivage du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/comptes/{id}/statistics",
     *      operationId="getCompteStatistics",
     *      tags={"Comptes"},
     *      summary="Obtenir les statistiques d'un compte spécifique",
     *      description="Retourne les statistiques (total dépôt, total retrait, nombre de transactions, dernière transaction) pour un compte bancaire par son ID.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID du compte pour lequel récupérer les statistiques",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Statistiques du compte récupérées avec succès"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="total_depot", type="number", format="float", example=5000.00),
     *                  @OA\Property(property="total_retrait", type="number", format="float", example=1500.00),
     *                  @OA\Property(property="nombre_transactions", type="integer", example=25),
     *                  @OA\Property(property="derniere_transaction", type="object", ref="#/components/schemas/TransactionResource", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Compte non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Compte non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function getCompteStatistics(string $id)
    {
        try {
            $compte = Compte::find($id);

            if (!$compte) {
                return $this->error('Compte non trouvé.', 404, 'COMPTE_NOT_FOUND');
            }

            $totalDepot = $compte->transactions()->where('type', 'credit')->sum('montant');
            $totalRetrait = $compte->transactions()->where('type', 'debit')->sum('montant');
            $nombreTransactions = $compte->transactions()->count();
            $derniereTransaction = $compte->transactions()->orderBy('date_transaction', 'desc')->first();

            $data = [
                'total_depot' => $totalDepot,
                'total_retrait' => $totalRetrait,
                'nombre_transactions' => $nombreTransactions,
                'derniere_transaction' => $derniereTransaction ? new \App\Http\Resources\TransactionResource($derniereTransaction) : null,
            ];

            return $this->success($data, 'Statistiques du compte récupérées avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques du compte: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors de la récupération des statistiques du compte.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }
}
