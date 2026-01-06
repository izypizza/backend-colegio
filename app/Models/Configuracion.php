<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'categoria'
    ];

    /**
     * Obtener valor de configuración con cache
     */
    public static function obtener(string $clave, $default = null)
    {
        return Cache::remember("config_{$clave}", 3600, function () use ($clave, $default) {
            $config = self::where('clave', $clave)->first();
            
            if (!$config) {
                return $default;
            }

            // Convertir según tipo
            return match($config->tipo) {
                'boolean' => filter_var($config->valor, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $config->valor,
                'json' => json_decode($config->valor, true),
                default => $config->valor,
            };
        });
    }

    /**
     * Establecer valor de configuración y limpiar cache
     */
    public static function establecer(string $clave, $valor): void
    {
        $config = self::where('clave', $clave)->first();
        
        if ($config) {
            // Convertir valor según tipo
            $valorString = match($config->tipo) {
                'boolean' => is_bool($valor) 
                    ? ($valor ? 'true' : 'false')
                    : ($valor === 'true' || $valor === true || $valor === 1 || $valor === '1' ? 'true' : 'false'),
                'json' => json_encode($valor),
                default => (string) $valor,
            };
            
            $config->update(['valor' => $valorString]);
            Cache::forget("config_{$clave}");
        }
    }

    /**
     * Limpiar toda la cache de configuraciones
     */
    public static function limpiarCache(): void
    {
        Cache::flush();
    }
}
