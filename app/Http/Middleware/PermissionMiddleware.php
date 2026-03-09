<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Configuración de permisos por módulo y acción.
     * Debe coincidir con frontend-colegio/src/lib/permissions.ts
     */
    private const PERMISSIONS = [
        'estudiantes' => [
            'view' => ['admin', 'auxiliar', 'docente'],
            'create' => ['admin', 'auxiliar'],
            'update' => ['admin', 'auxiliar'],
            'delete' => ['admin'],
        ],
        'docentes' => [
            'view' => ['admin', 'auxiliar'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'padres' => [
            'view' => ['admin', 'auxiliar'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'materias' => [
            'view' => ['admin', 'auxiliar', 'docente', 'padre', 'estudiante'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'secciones' => [
            'view' => ['admin', 'auxiliar', 'docente', 'padre', 'estudiante'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'grados' => [
            'view' => ['admin', 'auxiliar', 'docente'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'periodos' => [
            'view' => ['admin', 'auxiliar', 'docente', 'estudiante', 'padre'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'horarios' => [
            'view' => ['admin', 'auxiliar', 'docente', 'padre', 'estudiante'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'calificaciones' => [
            'view' => ['admin', 'auxiliar', 'docente', 'estudiante', 'padre'],
            'create' => ['admin', 'auxiliar', 'docente'],
            'update' => ['admin', 'auxiliar', 'docente'],
            'delete' => ['admin'],
        ],
        'asistencias' => [
            'view' => ['admin', 'auxiliar', 'docente'],
            'create' => ['admin', 'auxiliar', 'docente'],
            'update' => ['admin', 'auxiliar', 'docente'],
            'delete' => ['admin'],
        ],
        'usuarios' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'auxiliares' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'bibliotecarios' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'permisos_auxiliares' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'biblioteca' => [
            'view' => ['admin', 'bibliotecario', 'estudiante', 'docente'],
            'create' => ['admin', 'bibliotecario'],
            'update' => ['admin', 'bibliotecario'],
            'delete' => ['admin', 'bibliotecario'],
        ],
        'prestamos' => [
            'view' => ['admin', 'bibliotecario'],
            'create' => ['admin', 'bibliotecario'],
            'update' => ['admin', 'bibliotecario'],
            'delete' => ['admin'],
        ],
        'configuraciones' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'auditoria' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
    ];

    /**
     * Mapeo de métodos HTTP a acciones de permisos
     */
    private const HTTP_METHOD_MAP = [
        'GET' => 'view',
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module - Nombre del módulo (docentes, estudiantes, etc.)
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        // Verificar autenticación
        if (!$user) {
            return response()->json([
                'error' => 'No autenticado',
                'message' => 'Debe iniciar sesión para acceder a este recurso'
            ], 401);
        }

        // Obtener la acción según el método HTTP
        $httpMethod = $request->method();
        $action = self::HTTP_METHOD_MAP[$httpMethod] ?? 'view';

        // Verificar si el módulo tiene configuración de permisos
        if (!isset(self::PERMISSIONS[$module])) {
            \Log::warning("Módulo sin configuración de permisos: {$module}");
            // Por seguridad, denegar acceso si no hay configuración
            return response()->json([
                'error' => 'Módulo no configurado',
                'message' => 'Este módulo no tiene permisos configurados'
            ], 403);
        }

        // Verificar si la acción existe para este módulo
        if (!isset(self::PERMISSIONS[$module][$action])) {
            return response()->json([
                'error' => 'Acción no permitida',
                'message' => 'Esta acción no está disponible para este módulo'
            ], 403);
        }

        // Obtener roles permitidos para esta acción
        $allowedRoles = self::PERMISSIONS[$module][$action];

        // Verificar si el rol del usuario está permitido
        if (!in_array($user->role, $allowedRoles)) {
            \Log::info("Acceso denegado: Usuario {$user->id} ({$user->role}) intentó {$action} en {$module}");
            
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'No tiene permisos para realizar esta acción',
                'details' => [
                    'module' => $module,
                    'action' => $action,
                    'required_roles' => $allowedRoles,
                    'user_role' => $user->role
                ]
            ], 403);
        }

        // Log de acceso exitoso (opcional, para auditoría)
        \Log::info("Acceso autorizado: Usuario {$user->id} ({$user->role}) - {$action} en {$module}");

        return $next($request);
    }

    /**
     * Verificar si un usuario tiene permiso para una acción específica
     * (Método auxiliar para uso en controladores)
     */
    public static function hasPermission(string $module, string $action, $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!isset(self::PERMISSIONS[$module][$action])) {
            return false;
        }

        return in_array($user->role, self::PERMISSIONS[$module][$action]);
    }

    /**
     * Obtener todos los permisos configurados
     * (Útil para documentación o debugging)
     */
    public static function getAllPermissions(): array
    {
        return self::PERMISSIONS;
    }
}
