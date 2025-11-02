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
        // Supprimer tous les clients personnels existants
        Client::where('personal_access_client', true)->delete();

        // Créer un nouveau client d'accès personnel (sans spécifier d'ID pour auto-incrémentation)
        $client = Client::create([
            'name' => 'API Personal Access Client',
            'secret' => 'zAwdgWk80G8UxftueuRd2yGc12eNFUUXsYsBSbdD',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
        ]);

        echo "Client Passport créé avec ID: {$client->id}\n";
    }
}
