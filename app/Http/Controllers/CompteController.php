<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCompteRequest; // Assuming this is for creation, not listing
use App\Http\Resources\CompteResource;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;

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
        $query = Compte::query();

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
            if (isset($data['client']['id'])) {
                $client = \App\Models\Client::find($data['client']['id']);
            } else {
                // Chercher par email ou téléphone
                $client = \App\Models\Client::where('email', $data['client']['email'])
                    ->orWhere('telephone', $data['client']['telephone'])
                    ->first();
            }

            if (!$client) {
                // Créer le client avec mot de passe et code générés
                $password = \Illuminate\Support\Str::random(8);
                $code = \Illuminate\Support\Str::random(6); // ou random_int(100000, 999999)

                $client = \App\Models\Client::create([
                    'titulaire' => $data['client']['titulaire'],
                    'nci' => $data['client']['nci'],
                    'email' => $data['client']['email'],
                    'telephone' => $data['client']['telephone'],
                    'adresse' => $data['client']['adresse'],
                    'password' => bcrypt($password),
                    'code' => $code,
                ]);
            } else {
                // Si le client existe, assurez-vous que les données du client dans la requête correspondent
                // ou mettez à jour si nécessaire (selon la logique métier)
                // Pour l'instant, nous supposons que si un ID est fourni, les autres champs sont pour information ou ignorés.
                // Si le client est trouvé par email/téléphone, les données de la requête sont utilisées pour la création du compte.
            }

            // Générer numéro de compte unique
            do {
                $numeroCompte = 'C' . str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Compte::where('numeroCompte', $numeroCompte)->exists());

            // Créer le compte
            $compte = Compte::create([
                'numeroCompte' => $numeroCompte,
                'titulaire' => $client->titulaire, // Utiliser le titulaire du client trouvé ou créé
                'type' => $data['type'],
                'solde' => $data['solde'],
                'devise' => $data['devise'],
                'statut' => 'actif',
                'dateCreation' => now(),
                'client_id' => $client->id,
            ]);

            // Envoyer email et SMS (via event/listener)
            \Illuminate\Support\Facades\Event::dispatch(new \App\Events\ClientCreated($client, $password ?? null, $code ?? null));

            return $this->success(new CompteResource($compte), 'Compte créé avec succès', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, 'VALIDATION_ERROR', $e->errors(), $request->fullUrl());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
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
     $query = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)->onlyTrashed(); // Retrieve only soft-deleted accounts

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
 }
