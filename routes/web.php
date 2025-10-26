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
        $documentation = 'default';
        $urlToDocs = url('/api/docs');
        $configUrl = null;
        $validatorUrl = null;
        $useAbsolutePath = true;
        $operationsSorter = null;
        return view('vendor.l5-swagger.index', compact('documentation', 'urlToDocs', 'configUrl', 'validatorUrl', 'useAbsolutePath', 'operationsSorter'));
    });

    Route::get('/docs', function () {
        $documentation = 'default';
        $urlToDocs = url('/api/docs');
        $configUrl = null;
        $validatorUrl = null;
        $useAbsolutePath = true;
        $operationsSorter = null;
        return view('vendor.l5-swagger.index', compact('documentation', 'urlToDocs', 'configUrl', 'validatorUrl', 'useAbsolutePath', 'operationsSorter'));
    });

    // Route pour servir le fichier JSON de documentation
    Route::get('/api/docs', function () {
        $path = storage_path('api-docs/api-docs.json');
        if (file_exists($path)) {
            return response()->file($path, ['Content-Type' => 'application/json']);
        }
        return response()->json(['error' => 'Documentation not found'], 404);
    });
});

