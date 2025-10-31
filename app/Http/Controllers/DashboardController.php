<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Transaction;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\DashboardResource;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isClient()) {
            return $this->clientDashboard();
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

    private function clientDashboard()
    {
        // Assuming client has accounts via client_id, but user is separate.
        // For simplicity, assume client has same email as user or link via id.
        // Here, assume user.id == client.id for demo.

        $clientId = auth()->id(); // Assuming user.id == client.id

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
            'comptes' => $comptes,
        ];

        return $this->success(new DashboardResource($data), 'Dashboard client récupéré');
    }
}
