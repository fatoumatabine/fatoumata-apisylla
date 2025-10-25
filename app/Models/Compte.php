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
        // Pour l'instant, retourner la valeur stockée
        // Plus tard, calculer : somme dépôts - somme retraits
        return $value;
    }
}
