<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Transaction;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\DashboardResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CompteController;
use App\Http\Resources\CompteResource;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isClient()) {
            $compteId = $request->query('compte_id');
            return $this->clientDashboard($compteId);
        }

        return $this->error('Rôle non autorisé', 403);
    }

    private function adminDashboard()
    {
        $totalComptes = Compte::count();
        $balance = Transaction::where('type', 'credit')->sum('montant') - Transaction::where('type', 'debit')->sum('montant');
        $totalTransactions = Transaction::count();
        $recentTransactions = Transaction::with('compte')->orderBy('date_transaction', 'desc')->limit(10)->get();
        $comptesToday = Compte::whereDate('date_creation', today())->count();

        $data = [
            'total_comptes' => $totalComptes,
            'balance' => $balance,
            'total_transactions' => $totalTransactions,
            'recent_transactions' => $recentTransactions,
            'comptes_today' => $comptesToday,
        ];

        return $this->success(new DashboardResource($data), 'Dashboard admin récupéré');
    }

    private function clientDashboard(?string $compteId = null)
    {
        $clientId = auth()->id();

        if ($compteId) {
            $compte = Compte::where('client_id', $clientId)->find($compteId);

            if (!$compte) {
                return $this->error('Compte non trouvé pour ce client.', 404, 'COMPTE_NOT_FOUND');
            }

            $compteController = new CompteController();
            $statsResponse = $compteController->getCompteStatistics($compteId);
            $statsData = json_decode($statsResponse->getContent(), true)['data'];

            $data = [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numeroCompte,
                'total_depot' => $statsData['total_depot'],
                'total_retrait' => $statsData['total_retrait'],
                'nombre_transactions' => $statsData['nombre_transactions'],
                'derniere_transaction' => $statsData['derniere_transaction'],
            ];

            return $this->success(new DashboardResource($data), 'Statistiques du compte client récupérées avec succès');

        } else {
            $comptes = Compte::where('client_id', $clientId)->get();
            $totalComptes = $comptes->count();

            $balance = 0;
            $totalTransactions = 0;
            foreach ($comptes as $compte) {
                $credits = $compte->transactions()->where('type', 'credit')->sum('montant');
                $debits = $compte->transactions()->where('type', 'debit')->sum('montant');
                $balance += $credits - $debits;
                $totalTransactions += $compte->transactions()->count();
            }

            $recentTransactions = Transaction::whereIn('compte_id', $comptes->pluck('id'))
                ->orderBy('date_transaction', 'desc')
                ->limit(10)
                ->get();

            $data = [
                'total_comptes' => $totalComptes,
                'balance' => $balance,
                'total_transactions' => $totalTransactions,
                'recent_transactions' => $recentTransactions,
                'comptes' => CompteResource::collection($comptes),
            ];

            return $this->success(new DashboardResource($data), 'Dashboard client global récupéré');
        }
    }
}
