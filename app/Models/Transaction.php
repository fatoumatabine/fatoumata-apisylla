<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    // Constantes pour les types de transaction
    const TYPE_CREDIT = 'credit'; // Dépôt
    const TYPE_DEBIT = 'debit';   // Retrait

    // Constantes pour les statuts
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'devise',
        'description',
        'dateTransaction',
        'status',
        'archived',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'dateTransaction' => 'datetime',
        'archived' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = 'TXN-' . strtoupper(uniqid());
            }
            if (empty($transaction->dateTransaction)) {
                $transaction->dateTransaction = now();
            }
            if (empty($transaction->status)) {
                $transaction->status = self::STATUS_COMPLETED;
            }
        });
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    // Vérifier si la transaction est un crédit (dépôt)
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    // Vérifier si la transaction est un débit (retrait)
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    // Vérifier si la transaction est de aujourd'hui
    public function isToday(): bool
    {
        return $this->dateTransaction->isToday();
    }

    // Vérifier si la transaction peut être effectuée
    public function canBeProcessed(): bool
    {
        if ($this->isDebit()) {
            $compte = $this->compte;
            return $compte && $compte->solde_disponible >= $this->montant;
        }

        return true; // Les crédits sont toujours possibles
    }

    // Scope pour les transactions archivées
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    // Scope pour les transactions non archivées
    public function scopeNotArchived($query)
    {
        return $query->where('archived', false);
    }

    // Scope pour les transactions d'aujourd'hui
    public function scopeToday($query)
    {
        return $query->whereDate('dateTransaction', Carbon::today());
    }

    // Scope pour les transactions par type
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
