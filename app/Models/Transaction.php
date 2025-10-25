<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'devise',
        'description',
        'dateTransaction',
        'archived',
    ];

    protected $casts = [
        'dateTransaction' => 'datetime',
        'archived' => 'boolean',
    ];

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }
}
