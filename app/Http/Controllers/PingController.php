<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

class PingController extends Controller
{
    /**
     * Verificación de disponibilidad.
     * Consulta la base de datos para despertar el servicio y validar la conexión.
     */
    public function __invoke()
    {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'ok' => true,
                'database' => true,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $excepcion) {
            return response()->json([
                'ok' => false,
                'database' => false,
                'message' => 'La base de datos todavía no está disponible',
            ], 503);
        }
    }
}
