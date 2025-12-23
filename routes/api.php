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
use App\Http\Controllers\PadreController;
use App\Http\Controllers\GradoController;

// Rutas de autenticación (públicas)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ========================================
    // RUTAS CON CONTROL DE ACCESO POR ROLES
    // ========================================

    // Solo Admin - Gestión completa del sistema
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('grados', GradoController::class);
        Route::apiResource('materias', MateriaController::class);
        Route::apiResource('periodos', PeriodoAcademicoController::class);
    });

    // Admin o Auxiliar - Personal administrativo
    Route::middleware(['role:admin,auxiliar'])->group(function () {
        Route::apiResource('estudiantes', EstudianteController::class);
        Route::apiResource('asistencias', AsistenciaController::class);
        Route::apiResource('calificaciones', CalificacionController::class);
        Route::apiResource('secciones', SeccionController::class);
    });

    // Admin, Auxiliar o Docente - Gestión académica
    Route::middleware(['role:admin,auxiliar,docente'])->group(function () {
        Route::apiResource('docentes', DocenteController::class);
        Route::apiResource('horarios', HorarioController::class);
        Route::apiResource('asignaciones', AsignacionDocenteMateriaController::class);
    });

    // Admin, Auxiliar, Docente o Padre - Información general
    Route::middleware(['role:admin,auxiliar,docente,padre'])->group(function () {
        Route::apiResource('padres', PadreController::class);
    });
});
