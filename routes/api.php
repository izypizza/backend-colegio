<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\PeriodoAcademicoController;
use App\Http\Controllers\AsignacionDocenteMateriaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\CalificacionController;

// Rutas de autenticación (públicas)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
Route::apiResource('secciones', SeccionController::class);
Route::apiResource('docentes', DocenteController::class);
Route::apiResource('materias', MateriaController::class);
Route::apiResource('estudiantes', EstudianteController::class);
Route::apiResource('horarios', HorarioController::class);
Route::apiResource('periodos', PeriodoAcademicoController::class);
Route::apiResource('asignaciones', AsignacionDocenteMateriaController::class);
Route::apiResource('asistencias', AsistenciaController::class);
Route::apiResource('calificaciones', CalificacionController::class);
