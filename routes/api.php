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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\CategoriaLibroController;
use App\Http\Controllers\PrestamoLibroController;
use App\Http\Controllers\EleccionController;
use App\Http\Controllers\VotoController;
use App\Http\Controllers\DocentePortalController;
use App\Http\Controllers\EstudiantePortalController;
use App\Http\Controllers\PadrePortalController;

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

    // Dashboard - Estadísticas según rol
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ========================================
    // RUTAS CON CONTROL DE ACCESO POR ROLES
    // ========================================

    // Solo Admin - Gestión completa del sistema
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('grados', GradoController::class);
        Route::apiResource('materias', MateriaController::class);
        Route::apiResource('periodos', PeriodoAcademicoController::class)->except(['index', 'show']);
    });

    // Docentes, Padres y Estudiantes pueden VER períodos, grados, materias y secciones (solo lectura)
    Route::middleware(['role:admin,auxiliar,docente,padre,estudiante'])->group(function () {
        Route::get('/periodos', [PeriodoAcademicoController::class, 'index']);
        Route::get('/periodos/{periodo}', [PeriodoAcademicoController::class, 'show']);
        Route::get('/grados', [GradoController::class, 'index']);
        Route::get('/grados/{grado}', [GradoController::class, 'show']);
        Route::get('/materias', [MateriaController::class, 'index']);
        Route::get('/materias/{materia}', [MateriaController::class, 'show']);
        Route::get('/secciones', [SeccionController::class, 'index']);
        Route::get('/secciones/{seccion}', [SeccionController::class, 'show']);
        Route::get('/horarios', [HorarioController::class, 'index']);
        Route::get('/horarios/{horario}', [HorarioController::class, 'show']);
    });

    // Admin o Auxiliar - Personal administrativo
    Route::middleware(['role:admin,auxiliar'])->group(function () {
        Route::apiResource('estudiantes', EstudianteController::class);
        Route::apiResource('asistencias', AsistenciaController::class);
        Route::apiResource('calificaciones', CalificacionController::class);
        Route::apiResource('secciones', SeccionController::class)->except(['index', 'show']);
        Route::apiResource('horarios', HorarioController::class)->except(['index', 'show']);
        Route::apiResource('grados', GradoController::class)->except(['index', 'show']);
        Route::apiResource('materias', MateriaController::class)->except(['index', 'show']);
        
        // Reportes de asistencias
        Route::get('/asistencias/reporte/estudiante/{estudiante_id}', [AsistenciaController::class, 'reportePorEstudiante']);
        Route::get('/asistencias/reporte/seccion/{seccion_id}', [AsistenciaController::class, 'reportePorSeccion']);
        
        // Reportes de calificaciones
        Route::get('/calificaciones/boletin/{estudiante_id}/{periodo_id}', [CalificacionController::class, 'boletin']);
        Route::get('/calificaciones/reporte/materia/{materia_id}', [CalificacionController::class, 'reportePorMateria']);
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

    // Padres - Ver calificaciones de sus hijos
    Route::middleware(['role:padre'])->group(function () {
        Route::get('/mis-hijos-calificaciones', [CalificacionController::class, 'misHijosCalificaciones']);
    });

    // ========================================
    // SISTEMA DE BIBLIOTECA
    // ========================================
    
    // Admin o Auxiliar - Gestión de biblioteca
    Route::middleware(['role:admin,auxiliar'])->group(function () {
        Route::apiResource('categorias-libros', CategoriaLibroController::class);
        Route::apiResource('libros', LibroController::class);
        Route::apiResource('prestamos', PrestamoLibroController::class)->only(['index', 'store']);
        Route::post('/prestamos/{id}/devolver', [PrestamoLibroController::class, 'devolver']);
    });

    // Todos los usuarios autenticados pueden consultar libros y sus préstamos
    Route::get('/libros-disponibles', [LibroController::class, 'index']);
    Route::get('/mis-prestamos', [PrestamoLibroController::class, 'misPrestamos']);

    // ========================================
    // SISTEMA DE ELECCIONES ESCOLARES
    // ========================================
    
    // Admin - Gestión de elecciones
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('elecciones', EleccionController::class);
    });

    // Estudiantes - Votar en elecciones
    Route::middleware(['role:estudiante'])->group(function () {
        Route::post('/votos', [VotoController::class, 'store']);
        Route::get('/mis-votos', [VotoController::class, 'misVotos']);
    });

    // Todos pueden ver elecciones y resultados
    Route::get('/elecciones/{id}/resultados', [EleccionController::class, 'resultados']);
    Route::get('/elecciones/{id}/ya-vote', [EleccionController::class, 'yaVote']);

    // ========================================
    // PORTAL DOCENTE
    // ========================================
    Route::middleware(['role:docente'])->group(function () {
        Route::get('/docente/mis-asignaciones', [DocentePortalController::class, 'misAsignaciones']);
        Route::get('/docente/mis-estudiantes', [DocentePortalController::class, 'misEstudiantes']);
        Route::post('/docente/registrar-asistencia', [DocentePortalController::class, 'registrarAsistencia']);
        Route::post('/docente/registrar-calificacion', [DocentePortalController::class, 'registrarCalificacion']);
        Route::get('/docente/mis-calificaciones', [DocentePortalController::class, 'misCalificaciones']);
        Route::get('/docente/mis-asistencias', [DocentePortalController::class, 'misAsistencias']);
    });

    // ========================================
    // PORTAL ESTUDIANTE
    // ========================================
    Route::middleware(['role:estudiante'])->group(function () {
        Route::get('/estudiante/mis-calificaciones', [EstudiantePortalController::class, 'misCalificaciones']);
        Route::get('/estudiante/mis-asistencias', [EstudiantePortalController::class, 'misAsistencias']);
        Route::get('/estudiante/mi-perfil', [EstudiantePortalController::class, 'miPerfil']);
        Route::get('/estudiante/mi-boletin/{periodo_id}', [EstudiantePortalController::class, 'miBoletin']);
    });

    // ========================================
    // PORTAL PADRE
    // ========================================
    Route::middleware(['role:padre'])->group(function () {
        Route::get('/padre/mis-hijos', [PadrePortalController::class, 'misHijos']);
        Route::get('/padre/calificaciones-hijos', [PadrePortalController::class, 'calificacionesHijos']);
        Route::get('/padre/asistencias-hijo/{hijo_id}', [PadrePortalController::class, 'asistenciasHijo']);
        Route::get('/padre/boletin-hijo/{hijo_id}/{periodo_id}', [PadrePortalController::class, 'boletinHijo']);
    });
});
