<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCompteRequest; // Assuming this is for creation, not listing
use App\Http\Resources\CompteResource;
use App\Http\Traits\ApiResponseTrait;

class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
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
    * Store a newly created resource in storage.
    */
    public function store(StoreCompteRequest $request)
    {
       $data = $request->validated();

    // Vérifier si le client existe
    $client = null;
    if ($data['client']['id']) {
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
        }

        // Générer numéro de compte
        $numeroCompte = 'C' . strtoupper(\Illuminate\Support\Str::random(9));

        // Créer le compte
        $compte = Compte::create([
            'numeroCompte' => $numeroCompte,
            'titulaire' => $data['client']['titulaire'],
            'type' => $data['type'],
            'solde' => $data['solde'],
            'devise' => $data['devise'],
            'statut' => 'actif',
            'dateCreation' => now(),
            'client_id' => $client->id,
        ]);

        // Envoyer email et SMS (via event/listener)
        \Illuminate\Support\Facades\Event::dispatch(new \App\Events\ClientCreated($client, $password, $code));

        return $this->success(new CompteResource($compte), 'Compte créé avec succès', 201);
    }

    /**
     * Display a listing of archived accounts.
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
