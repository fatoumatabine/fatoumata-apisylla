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
    return 'API Comptes - Bienvenue ! Utilisez /api/v1/comptes pour accéder aux comptes.';
});

// Routes Swagger pour la documentation API
Route::group(['middleware' => []], function () {
    Route::get('/api/documentation', function () {
        return view('vendor.l5-swagger.index');
    });

    Route::get('/docs', function () {
        return view('vendor.l5-swagger.index');
    });
});

