<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Comptes - Bienvenue',
        'version' => '1.0.0',
        'endpoints' => [
            'comptes' => '/api/v1/comptes',
            'comptes_archives' => '/api/v1/comptes/archived'
        ]
    ]);
});
