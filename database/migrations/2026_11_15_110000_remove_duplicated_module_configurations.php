<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina claves duplicadas o sin efecto.
     *
     * modulo_*_activo duplicaba modulos_* (que es la que consume el endpoint
     * de módulos activos). Las de accesibilidad se guardan en el navegador,
     * no en la base, así que nunca se leyeron.
     */
    public function up(): void
    {
        DB::table('configuraciones')->whereIn('clave', [
            'modulo_biblioteca_activo',
            'modulo_calificaciones_activo',
            'modulo_elecciones_activo',
            'tema_tamano_fuente',
        ])->delete();

        DB::table('configuraciones')->where('clave', 'like', 'accesibilidad%')->delete();
    }

    public function down(): void
    {
    }
};
