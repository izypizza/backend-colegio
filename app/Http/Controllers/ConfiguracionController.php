<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ConfiguracionController extends Controller
{
    /**
     * Obtener todas las configuraciones agrupadas por categoría
     */
    public function index()
    {
        $configuraciones = Configuracion::all()->groupBy('categoria');
        
        return response()->json($configuraciones);
    }

    /**
     * Obtener una configuración específica
     */
    public function obtener(string $clave)
    {
        $valor = Configuracion::obtener($clave);
        
        return response()->json([
            'clave' => $clave,
            'valor' => $valor
        ]);
    }

    /**
     * Actualizar configuraciones
     */
    public function actualizar(Request $request)
    {
        try {
            $validated = $request->validate([
                'configuraciones' => 'required|array',
                'configuraciones.*.clave' => 'required|string',
                'configuraciones.*.valor' => 'required',
            ]);

            foreach ($validated['configuraciones'] as $config) {
                Configuracion::establecer($config['clave'], $config['valor']);
            }

            return response()->json([
                'message' => 'Configuraciones actualizadas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar configuraciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar cache del sistema
     */
    public function limpiarCache()
    {
        try {
            // Limpiar cache de Laravel
            Cache::flush();
            
            // Limpiar cache de configuración
            Artisan::call('config:clear');
            
            // Limpiar cache de rutas
            Artisan::call('route:clear');
            
            // Limpiar cache de vistas
            Artisan::call('view:clear');

            return response()->json([
                'message' => 'Cache limpiado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al limpiar cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del sistema
     */
    public function infoSistema()
    {
        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'timezone' => config('app.timezone'),
        ]);
    }
}
