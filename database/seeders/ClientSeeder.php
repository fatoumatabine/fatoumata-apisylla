<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
/**
* Run the database seeds.
 */
public function run(): void
{
// Créer un client lié à l'utilisateur client
$clientUser = User::where('role', 'client')->first();

if ($clientUser) {
Client::updateOrCreate(
['email' => 'client@example.com'],
[
    'titulaire' => 'Client Test User',
    'nci' => '1234567890123',
    'telephone' => '+221771234567',
    'adresse' => 'Dakar, Sénégal',
    'password' => bcrypt('password'),
        'user_id' => $clientUser->id,
        ]
        );
        }
    }
}
