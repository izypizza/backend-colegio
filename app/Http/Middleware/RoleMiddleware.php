<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Middleware para verificar el rol del usuario autenticado.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles - Roles permitidos (admin, auxiliar, docente, padre, estudiante)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            return response()->json([
                'error' => 'No autenticado',
                'message' => 'Debe iniciar sesión para acceder a este recurso'
            ], 401);
        }

        // Verificar si el usuario tiene uno de los roles permitidos
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'No tiene permisos para acceder a este recurso',
                'required_roles' => $roles,
                'user_role' => $request->user()->role
            ], 403);
        }

        return $next($request);
    }
}
