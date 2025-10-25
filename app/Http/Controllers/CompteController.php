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
