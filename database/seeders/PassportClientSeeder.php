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
    // Créer un client d'accès personnel si aucun n'existe
        if (Client::where('personal_access_client', true)->doesntExist()) {
            Client::create([
                'name' => 'API Personal Access Client',
                'secret' => 'zAwdgWk80G8UxftueuRd2yGc12eNFUUXsYsBSbdD', // À remplacer en production
                'redirect' => '',
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
            ]);
        }
    }
}
