<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\Client;

class PassportClientSeeder extends Seeder
{
/**
* Run the database seeds.
 */
public function run(): void
{
// Supprimer tous les clients existants
Client::query()->delete();

// Créer un nouveau client d'accès personnel
Client::create([
'id' => 'a0438b0b-64f8-42e3-9237-917e0c688d8f', // Utiliser le même ID que précédemment
'name' => 'API Personal Access Client',
'secret' => 'zAwdgWk80G8UxftueuRd2yGc12eNFUUXsYsBSbdD',
'redirect' => 'http://localhost',
'personal_access_client' => true,
    'password_client' => false,
        'revoked' => false,
        ]);

        echo "Client Passport créé avec succès.\n";
    }
}
