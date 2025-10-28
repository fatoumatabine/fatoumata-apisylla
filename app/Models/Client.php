<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

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

    protected $hidden = [
        'password',
        'code',
    ];

    protected $casts = [
        'password' => 'hashed',
        'code' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->password)) {
                $model->password = Hash::make(strtoupper(substr(md5(uniqid()), 0, 8)));
            }
            if (empty($model->code)) {
                $model->code = Hash::make(strtoupper(substr(md5(uniqid()), 0, 6))); // Hacher le code
            }
        });
    }

    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class);
    }
}
