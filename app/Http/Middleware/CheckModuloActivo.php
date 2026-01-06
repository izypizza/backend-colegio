<?php

namespace App\Http\Middleware;

use App\Models\Configuracion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuloActivo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $modulo  Nombre de la configuración del módulo
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        // Verificar si el módulo está activo
        $activo = Configuracion::obtener($modulo, true);
        
        if (!$activo) {
            return response()->json([
                'message' => 'Este módulo está desactivado por el administrador',
                'modulo' => $modulo
            ], 403);
        }
        
        return $next($request);
    }
}
