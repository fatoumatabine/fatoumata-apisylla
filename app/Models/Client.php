<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
    'titulaire',
    'nci',
    'email',
    'telephone',
        'adresse',
        'password',
        'code',
    ];

    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class);
    }
}
