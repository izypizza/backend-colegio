<?php

use App\Http\Controllers\AsignacionDocenteMateriaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuxiliarPermisoController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\CategoriaLibroController;
use App\Http\Controllers\ChatConversacionController;
use App\Http\Controllers\ChatMensajeController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\DocentePortalController;
use App\Http\Controllers\EleccionController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\EstudiantePortalController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PadreController;
use App\Http\Controllers\PadrePortalController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\PeriodoAcademicoController;
use App\Http\Controllers\PrestamoLibroController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación (públicas)
Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas mobile (públicas)
Route::post('/mobile/auth/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Rutas mobile (protegidas)
    Route::prefix('mobile')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        Route::middleware(['role:docente'])->group(function () {
            Route::get('/docente/mis-asignaciones', [DocentePortalController::class, 'misAsignaciones']);
            Route::get('/docente/mis-estudiantes', [DocentePortalController::class, 'misEstudiantes']);
            Route::get('/docente/mis-calificaciones', [DocentePortalController::class, 'misCalificaciones']);
            Route::get('/docente/mis-asistencias', [DocentePortalController::class, 'misAsistencias']);
        });

        Route::middleware(['role:estudiante'])->group(function () {
            Route::get('/estudiante/mi-horario', [EstudiantePortalController::class, 'miHorario']);
            Route::get('/estudiante/mis-calificaciones', [EstudiantePortalController::class, 'misCalificaciones']);
            Route::get('/estudiante/mis-asistencias', [EstudiantePortalController::class, 'misAsistencias']);
        });

        Route::middleware(['role:padre'])->group(function () {
            Route::get('/padre/mis-hijos', [PadrePortalController::class, 'misHijos']);
            Route::get('/padre/calificaciones-hijos', [PadrePortalController::class, 'calificacionesHijos']);
            Route::get('/padre/asistencias-hijo/{hijo_id}', [PadrePortalController::class, 'asistenciasHijo']);
            Route::get('/padre/docentes-hijo/{hijo_id}', [PadrePortalController::class, 'docentesHijo']);
        });
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard - Estadísticas según rol
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Año académico - Disponible para todos los autenticados
    Route::get('/dashboard/anio-academico', [DashboardController::class, 'anioAcademico']);

    // ========================================
    // CONFIGURACIONES DEL SISTEMA - Con permisos granulares
    // ========================================
    Route::middleware(['permission:configuraciones'])->group(function () {
        Route::get('/configuraciones', [ConfiguracionController::class, 'index']);
        Route::get('/configuraciones/{clave}', [ConfiguracionController::class, 'obtener']);
        Route::post('/configuraciones', [ConfiguracionController::class, 'actualizar']);
        Route::post('/sistema/limpiar-cache', [ConfiguracionController::class, 'limpiarCache']);
        Route::get('/sistema/info', [ConfiguracionController::class, 'infoSistema']);
    });

    // Obtener módulos activos - Disponible para todos los autenticados
    Route::get('/sistema/modulos-activos', [ConfiguracionController::class, 'modulosActivos']);

    // ========================================
    // RUTAS CON CONTROL DE ACCESO POR ROLES
    // ========================================

    // Periodos académicos - Con permisos granulares
    Route::middleware(['permission:periodos'])->group(function () {
        Route::get('/periodos', [PeriodoAcademicoController::class, 'index']);
        Route::post('/periodos', [PeriodoAcademicoController::class, 'store']);
        Route::get('/periodos/{periodo}', [PeriodoAcademicoController::class, 'show']);
        Route::put('/periodos/{periodo}', [PeriodoAcademicoController::class, 'update']);
        Route::delete('/periodos/{periodo}', [PeriodoAcademicoController::class, 'destroy']);
        Route::post('/periodos/{id}/activar', [PeriodoAcademicoController::class, 'activar']);
        Route::post('/periodos/generar-anio', [PeriodoAcademicoController::class, 'generarAnio']);
    });

    // ========================================
    // ASISTENCIAS - Con permisos granulares
    // ========================================
    Route::middleware(['permission:asistencias'])->group(function () {
        Route::get('/asistencias', [AsistenciaController::class, 'index']);
        Route::post('/asistencias', [AsistenciaController::class, 'store']);
        Route::get('/asistencias/{id}', [AsistenciaController::class, 'show']);
        Route::put('/asistencias/{id}', [AsistenciaController::class, 'update']);
        Route::delete('/asistencias/{id}', [AsistenciaController::class, 'destroy']);

        // Reportes de asistencias
        Route::get('/asistencias/reporte/estudiante/{estudiante_id}', [AsistenciaController::class, 'reportePorEstudiante']);
        Route::get('/asistencias/reporte/seccion/{seccion_id}', [AsistenciaController::class, 'reportePorSeccion']);
    });

    // ========================================
    // CALIFICACIONES - Con permisos granulares
    // ========================================
    Route::middleware(['permission:calificaciones', 'modulo.activo:modulo_calificaciones_activo'])->group(function () {
        // Rutas específicas ANTES de las genéricas con {id}
        Route::get('/calificaciones/estadisticas-avanzadas', [CalificacionController::class, 'estadisticasAvanzadas']);
        Route::get('/calificaciones/boletin/{estudiante_id}/{periodo_id}', [CalificacionController::class, 'boletin']);
        Route::get('/calificaciones/reporte/materia/{materia_id}', [CalificacionController::class, 'reportePorMateria']);
        
        // Rutas genéricas de calificaciones
        Route::get('/calificaciones', [CalificacionController::class, 'index']);
        Route::post('/calificaciones', [CalificacionController::class, 'store']);
        Route::get('/calificaciones/{id}', [CalificacionController::class, 'show']);
        Route::put('/calificaciones/{id}', [CalificacionController::class, 'update']);
        Route::delete('/calificaciones/{id}', [CalificacionController::class, 'destroy']);
    });

    // ========================================
    // GESTIÓN ACADÉMICA - Con permisos granulares
    // ========================================
    
    Route::middleware(['permission:estudiantes'])->group(function () {
        Route::apiResource('estudiantes', EstudianteController::class);
    });
    
    Route::middleware(['permission:secciones'])->group(function () {
        Route::apiResource('secciones', SeccionController::class);
    });
    
    Route::middleware(['permission:horarios'])->group(function () {
        Route::apiResource('horarios', HorarioController::class);
    });
    
    Route::middleware(['permission:grados'])->group(function () {
        Route::apiResource('grados', GradoController::class);
    });
    
    Route::middleware(['permission:materias'])->group(function () {
        Route::apiResource('materias', MateriaController::class);
    });

    // ========================================
    // DOCENTES Y ASIGNACIONES - Con permisos granulares
    // ========================================
    Route::middleware(['permission:docentes'])->group(function () {
        Route::apiResource('docentes', DocenteController::class);
        Route::apiResource('asignaciones', AsignacionDocenteMateriaController::class);
    });

    // ========================================
    // PADRES - Con permisos granulares
    // ========================================
    Route::middleware(['permission:padres'])->group(function () {
        Route::apiResource('padres', PadreController::class);
        
        // Gestión de asociaciones padre-estudiante
        Route::post('/padres/{id}/asociar-estudiante', [PadreController::class, 'asociarEstudiante']);
        Route::delete('/padres/{padreId}/desasociar-estudiante/{estudianteId}', [PadreController::class, 'desasociarEstudiante']);
        Route::get('/padres/{id}/estudiantes-disponibles', [PadreController::class, 'estudiantesDisponibles']);
    });

    // Padres - Ver calificaciones de sus hijos
    Route::middleware(['role:padre'])->group(function () {
        Route::get('/mis-hijos-calificaciones', [CalificacionController::class, 'misHijosCalificaciones']);
    });

    // ========================================
    // SISTEMA DE BIBLIOTECA - Con permisos granulares
    // ========================================
    Route::middleware(['permission:biblioteca', 'modulo.activo:modulos_biblioteca'])->group(function () {
        Route::apiResource('categorias-libros', CategoriaLibroController::class);
        Route::apiResource('libros', LibroController::class);
    });
    
    Route::middleware(['permission:prestamos', 'modulo.activo:modulos_biblioteca'])->group(function () {
        Route::get('/prestamos', [PrestamoLibroController::class, 'index']);
        Route::post('/prestamos/{id}/aprobar', [PrestamoLibroController::class, 'aprobar']);
        Route::post('/prestamos/{id}/rechazar', [PrestamoLibroController::class, 'rechazar']);
        Route::post('/prestamos/{id}/devolver', [PrestamoLibroController::class, 'devolver']);
        Route::get('/biblioteca/reportes', [PrestamoLibroController::class, 'reportes']);
    });

    // Todos los usuarios autenticados pueden consultar libros, solicitar préstamos y ver sus préstamos
    Route::middleware(['modulo.activo:modulos_biblioteca'])->group(function () {
        Route::get('/libros-disponibles', [LibroController::class, 'index']);
        Route::post('/prestamos', [PrestamoLibroController::class, 'store']);
        Route::get('/mis-prestamos', [PrestamoLibroController::class, 'misPrestamos']);
    });

    // ========================================
    // SISTEMA DE ELECCIONES ESCOLARES
    // ========================================

    // Todos pueden ver elecciones y resultados (solo si están publicados)
    Route::middleware(['modulo.activo:modulos_elecciones'])->group(function () {
        Route::get('/elecciones', [EleccionController::class, 'index']);
        Route::get('/elecciones/{id}', [EleccionController::class, 'show']);
        Route::get('/elecciones/{id}/resultados', [EleccionController::class, 'resultados']);
        
        // Cualquier usuario autenticado puede verificar si ya votó
        Route::get('/elecciones/{id}/ya-vote', [EleccionController::class, 'yaVote']);

        // Admin - Gestión completa de elecciones (solo supervisión, no manipula votos)
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/elecciones', [EleccionController::class, 'store']);
            Route::put('/elecciones/{id}', [EleccionController::class, 'update']);
            Route::delete('/elecciones/{id}', [EleccionController::class, 'destroy']);
            Route::post('/elecciones/{id}/activar', [EleccionController::class, 'activar']);
            Route::post('/elecciones/{id}/cerrar', [EleccionController::class, 'cerrar']);
            Route::post('/elecciones/{id}/publicar-resultados', [EleccionController::class, 'publicarResultados']);
            
            // Gestión de partidos políticos
            Route::get('/partidos', [PartidoController::class, 'index']);
            Route::post('/partidos', [PartidoController::class, 'store']);
            Route::put('/partidos/{id}', [PartidoController::class, 'update']);
            Route::delete('/partidos/{id}', [PartidoController::class, 'destroy']);
        });

        // Estudiantes - Votar en elecciones activas
        Route::middleware(['role:estudiante', 'modulo.activo:modulos_elecciones'])->group(function () {
            Route::post('/votos', [VotoController::class, 'store']);
            Route::get('/mis-votos', [VotoController::class, 'misVotos']);
        });
    }); // Fin grupo módulo elecciones

    // ========================================
    // NOTIFICACIONES Y AUDITORIA
    // ========================================
    // Rutas de notificaciones para usuarios autenticados
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::get('/notificaciones/estadisticas', [NotificacionController::class, 'estadisticas']);
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
    Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy']);
    Route::delete('/notificaciones/limpiar-leidas', [NotificacionController::class, 'eliminarLeidas']);

    // Crear notificaciones y auditoría (solo admin)
    Route::middleware(['permission:auditoria'])->group(function () {
        Route::post('/notificaciones', [NotificacionController::class, 'store']);
        Route::get('/auditoria', [AuditLogController::class, 'index']);
    });

    // ========================================
    // CHAT DOCENTE - PADRE
    // ========================================
    Route::middleware(['role:admin,docente,padre'])->group(function () {
        Route::get('/chat/conversaciones', [ChatConversacionController::class, 'index']);
        Route::post('/chat/conversaciones', [ChatConversacionController::class, 'store']);
        Route::get('/chat/conversaciones/{id}/mensajes', [ChatMensajeController::class, 'index']);
        Route::post('/chat/conversaciones/{id}/mensajes', [ChatMensajeController::class, 'store']);
        Route::post('/chat/conversaciones/{conversacionId}/mensajes/{mensajeId}/leer', [ChatMensajeController::class, 'marcarLeido']);
        Route::get('/chat/mensajes/no-leidos', [ChatMensajeController::class, 'contarNoLeidos']);
    });

    // Admin - Monitoreo de chat
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/chat/todas', [ChatConversacionController::class, 'todas']);
        Route::get('/chat/estadisticas', [ChatConversacionController::class, 'estadisticas']);
    });

    // ========================================
    // REPORTES PDF/EXCEL
    // ========================================
    Route::middleware(['role:admin,auxiliar'])->group(function () {
        Route::get('/reportes/estudiantes/excel', [ReporteController::class, 'estudiantesExcel']);
        Route::get('/reportes/estudiantes/pdf', [ReporteController::class, 'estudiantesPdf']);
        Route::get('/reportes/calificaciones/excel', [ReporteController::class, 'calificacionesExcel']);
        Route::get('/reportes/calificaciones/pdf', [ReporteController::class, 'calificacionesPdf']);
    });

    // ========================================
    // SISTEMA DE PERMISOS ESPECIALES PARA AUXILIARES - Con permisos granulares
    // ========================================
    Route::middleware(['permission:permisos_auxiliares', 'modulo.activo:modulos_permisos'])->group(function () {
        Route::get('/auxiliares', [AuxiliarPermisoController::class, 'getAuxiliares']);
        Route::get('/auxiliar-permisos', [AuxiliarPermisoController::class, 'index']);
        Route::post('/auxiliar-permisos', [AuxiliarPermisoController::class, 'store']);
        Route::get('/auxiliar-permisos/{userId}', [AuxiliarPermisoController::class, 'show']);
        Route::delete('/auxiliar-permisos/{userId}', [AuxiliarPermisoController::class, 'destroy']);
    });

    // Auxiliar - Ver sus propios permisos
    Route::middleware(['role:auxiliar', 'modulo.activo:modulos_permisos'])->group(function () {
        Route::get('/mi-permiso-especial', [AuxiliarPermisoController::class, 'miPermiso']);
    });

    // ========================================
    // GESTIÓN DE USUARIOS Y PERSONAS - Con permisos granulares
    // ========================================
    Route::middleware(['permission:usuarios'])->group(function () {
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
        
        // Rutas de tutor
        Route::get('/docente/es-tutor', [DocentePortalController::class, 'esTutor']);
        Route::get('/docente/tutor-calificaciones', [DocentePortalController::class, 'tutorCalificaciones']);
        Route::get('/docente/tutor-asistencias', [DocentePortalController::class, 'tutorAsistencias']);
    });

    // ========================================
    // PORTAL ESTUDIANTE
    // ========================================
    Route::middleware(['role:estudiante'])->group(function () {
        Route::get('/estudiante/mi-horario', [EstudiantePortalController::class, 'miHorario']);
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
        Route::get('/padre/docentes-hijo/{hijo_id}', [PadrePortalController::class, 'docentesHijo']);
    });
}); // Fin middleware auth:sanctum
