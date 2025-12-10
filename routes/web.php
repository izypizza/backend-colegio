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
