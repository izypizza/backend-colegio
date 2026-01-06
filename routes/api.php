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
use App\Http\Controllers\AuxiliarPermisoController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ConfiguracionController;

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
    // CONFIGURACIONES DEL SISTEMA (Solo Admin)
    // ========================================
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/configuraciones', [ConfiguracionController::class, 'index']);
        Route::get('/configuraciones/{clave}', [ConfiguracionController::class, 'obtener']);
        Route::post('/configuraciones', [ConfiguracionController::class, 'actualizar']);
        Route::post('/sistema/limpiar-cache', [ConfiguracionController::class, 'limpiarCache']);
        Route::get('/sistema/info', [ConfiguracionController::class, 'infoSistema']);
    });

    // ========================================
    // RUTAS CON CONTROL DE ACCESO POR ROLES
    // ========================================

    // Solo Admin - Gestión completa del sistema (PROTEGIDO)
    Route::middleware(['role:admin'])->group(function () {
        // Grados y Materias requieren confirmación especial por ser datos permanentes
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
        
        // Reportes de asistencias (ANTES de apiResource para evitar conflictos)
        Route::get('/asistencias/reporte/estudiante/{estudiante_id}', [AsistenciaController::class, 'reportePorEstudiante']);
        Route::get('/asistencias/reporte/seccion/{seccion_id}', [AsistenciaController::class, 'reportePorSeccion']);
        
        // Calificaciones - Protegido por módulo activo
        Route::middleware(['modulo.activo:modulo_calificaciones_activo'])->group(function () {
            // Reportes de calificaciones (ANTES de apiResource para evitar conflictos)
            Route::get('/calificaciones/estadisticas-avanzadas', [CalificacionController::class, 'estadisticasAvanzadas']);
            Route::get('/calificaciones/boletin/{estudiante_id}/{periodo_id}', [CalificacionController::class, 'boletin']);
            Route::get('/calificaciones/reporte/materia/{materia_id}', [CalificacionController::class, 'reportePorMateria']);
            
            Route::apiResource('calificaciones', CalificacionController::class);
        });
        
        Route::apiResource('secciones', SeccionController::class)->except(['index', 'show']);
        Route::apiResource('horarios', HorarioController::class)->except(['index', 'show']);
        Route::apiResource('grados', GradoController::class)->except(['index', 'show']);
        Route::apiResource('materias', MateriaController::class)->except(['index', 'show']);
    });

    // Admin, Auxiliar o Docente - Gestión académica
    Route::middleware(['role:admin,auxiliar,docente'])->group(function () {
        Route::apiResource('docentes', DocenteController::class);
        Route::apiResource('horarios', HorarioController::class);
        Route::apiResource('asignaciones', AsignacionDocenteMateriaController::class);
    });

    // Docentes - Registrar asistencias y calificaciones de SUS estudiantes
    Route::middleware(['role:docente'])->group(function () {
        Route::post('/docente/asistencias', [AsistenciaController::class, 'store']);
        Route::put('/docente/asistencias/{id}', [AsistenciaController::class, 'update']);
        Route::post('/docente/calificaciones', [CalificacionController::class, 'store']);
        Route::put('/docente/calificaciones/{id}', [CalificacionController::class, 'update']);
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
    
    // Admin y Bibliotecario - Gestión completa de biblioteca
    Route::middleware(['role:admin,bibliotecario'])->group(function () {
        Route::apiResource('categorias-libros', CategoriaLibroController::class);
        Route::apiResource('libros', LibroController::class);
        Route::get('/prestamos', [PrestamoLibroController::class, 'index']);
        Route::post('/prestamos/{id}/devolver', [PrestamoLibroController::class, 'devolver']);
        Route::get('/biblioteca/reportes', [PrestamoLibroController::class, 'reportes']);
    });

    // Todos los usuarios autenticados pueden consultar libros, solicitar préstamos y ver sus préstamos
    Route::get('/libros-disponibles', [LibroController::class, 'index']);
    Route::post('/prestamos', [PrestamoLibroController::class, 'store']);
    Route::get('/mis-prestamos', [PrestamoLibroController::class, 'misPrestamos']);

    // ========================================
    // SISTEMA DE ELECCIONES ESCOLARES
    // ========================================
    
    // Admin - Gestión completa de elecciones (solo supervisión, no manipula votos)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('elecciones', EleccionController::class);
        Route::post('/elecciones/{id}/activar', [EleccionController::class, 'activar']);
        Route::post('/elecciones/{id}/cerrar', [EleccionController::class, 'cerrar']);
        Route::post('/elecciones/{id}/publicar-resultados', [EleccionController::class, 'publicarResultados']);
    });

    // Estudiantes - Votar en elecciones activas
    Route::middleware(['role:estudiante'])->group(function () {
        Route::post('/votos', [VotoController::class, 'store']);
        Route::get('/mis-votos', [VotoController::class, 'misVotos']);
    });

    // Todos pueden ver elecciones y resultados (solo si están publicados)
    Route::get('/elecciones', [EleccionController::class, 'index']);
    Route::get('/elecciones/{id}', [EleccionController::class, 'show']);
    Route::get('/elecciones/{id}/resultados', [EleccionController::class, 'resultados']);
    Route::get('/elecciones/{id}/ya-vote', [EleccionController::class, 'yaVote']);

    // ========================================
    // SISTEMA DE PERMISOS ESPECIALES PARA AUXILIARES
    // ========================================
    
    // Admin - Gestión de permisos especiales
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/auxiliares', [AuxiliarPermisoController::class, 'getAuxiliares']);
        Route::get('/auxiliar-permisos', [AuxiliarPermisoController::class, 'index']);
        Route::post('/auxiliar-permisos', [AuxiliarPermisoController::class, 'store']);
        Route::get('/auxiliar-permisos/{userId}', [AuxiliarPermisoController::class, 'show']);
        Route::delete('/auxiliar-permisos/{userId}', [AuxiliarPermisoController::class, 'destroy']);
    });

    // Auxiliar - Ver sus propios permisos
    Route::middleware(['role:auxiliar'])->group(function () {
        Route::get('/mi-permiso-especial', [AuxiliarPermisoController::class, 'miPermiso']);
    });

    // ========================================
    // GESTIÓN DE USUARIOS Y PERSONAS
    // ========================================
    
    // Admin - Gestión completa de usuarios
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{id}', [UserManagementController::class, 'update']);
        Route::post('/users/{id}/toggle-active', [UserManagementController::class, 'toggleActive']);
        Route::get('/personas-sin-usuario/{tipo}', [UserManagementController::class, 'personasSinUsuario']);
        Route::put('/estudiantes/{id}/estado', [UserManagementController::class, 'updateEstadoEstudiante']);
    });

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
