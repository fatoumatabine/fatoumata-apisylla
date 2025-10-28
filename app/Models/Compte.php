<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use App\Scopes\NonArchivedScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'numeroCompte',
        'titulaire',
        'type',
        'solde',
        'devise',
        'dateCreation',
        'statut',
        'metadata',
        'client_id',
        'date_debut_blocage',
        'date_fin_blocage',
        'archived',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dateCreation' => 'datetime',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
        'archived' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new NonArchivedScope);
        static::creating(function ($model) {
            if (empty($model->numeroCompte)) {
                $model->numeroCompte = (string) Str::uuid();
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeNumero($query, $numeroCompte)
    {
        return $query->where('numeroCompte', $numeroCompte);
    }

    public function scopeClient($query, $telephone)
    {
    return $query->whereHas('client', function ($q) use ($telephone) {
    $q->where('telephone', $telephone);
    });
    }

    // Attribut personnalisé pour le solde (calculé)
    public function getSoldeAttribute($value)
    {
        // Calculer le solde réel : somme dépôts - somme retraits
        $debits = $this->transactions()->where('type', 'debit')->sum('montant');
        $credits = $this->transactions()->where('type', 'credit')->sum('montant');

        return $credits - $debits;
    }

    // Calculer le solde disponible (pour les vérifications)
    public function getSoldeDisponibleAttribute()
    {
        // Pour les comptes épargne bloqués, le solde disponible peut être limité
        if ($this->type === 'epargne' && $this->statut === 'bloque') {
            // Logique métier pour les comptes bloqués
            return 0; // Par exemple, pas de retrait possible sur comptes bloqués
        }

        return $this->solde;
    }
}
