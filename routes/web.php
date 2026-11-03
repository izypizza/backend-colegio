<?php

use Illuminate\Support\Facades\Route;

// Backend API - Sin rutas web públicas
Route::get('/', function () {
    return response()->json([
        'message' => 'API Backend Colegio',
        'version' => '1.0.0',
        'status' => 'active'
    ]);
});

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Debes iniciar sesión para acceder a este recurso.',
        'code' => 'no_autenticado',
    ], 401);
})->name('login');
