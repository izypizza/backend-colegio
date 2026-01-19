<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Configuracion;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir rutas de autenticación sin verificar mantenimiento
        $exemptRoutes = [
            'api/auth/login',
            'api/auth/register',
        ];
        
        $currentPath = $request->path();
        
        foreach ($exemptRoutes as $route) {
            if ($currentPath === $route) {
                return $next($request);
            }
        }
        
        // Verificar si el modo mantenimiento está activado
        $modoMantenimiento = Configuracion::obtener('sistema_modo_mantenimiento', false);
        
        // Si está en mantenimiento
        if ($modoMantenimiento) {
            // Permitir acceso solo a admins autenticados
            $user = $request->user();
            
            if (!$user || $user->role !== 'admin') {
                $mensaje = Configuracion::obtener(
                    'sistema_mensaje_mantenimiento',
                    'El sistema está en mantenimiento. Por favor, inténtelo más tarde.'
                );
                
                return response()->json([
                    'error' => 'Sistema en mantenimiento',
                    'message' => $mensaje,
                    'maintenance_mode' => true
                ], 503);
            }
        }
        
        return $next($request);
    }
}
