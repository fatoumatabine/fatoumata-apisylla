<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComptePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent lister des comptes
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Compte $compte): bool
    {
        return $this->canAccessAccount($user, $compte);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin(); // Seuls les admins peuvent créer des comptes
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compte $compte): bool
    {
        return $this->canAccessAccount($user, $compte);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compte $compte): bool
    {
        return $user->isAdmin(); // Seuls les admins peuvent supprimer des comptes
    }

    /**
     * Determine whether the user can block/unblock the model.
     */
    public function block(User $user, Compte $compte): bool
    {
        return $user->isAdmin() && $compte->type === 'epargne';
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Compte $compte): bool
    {
        return $user->isAdmin() && $compte->type === 'epargne' && $compte->statut === 'bloque';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Compte $compte): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Compte $compte): bool
    {
        return $user->isAdmin();
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un compte spécifique
     */
    private function canAccessAccount(User $user, Compte $compte): bool
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
