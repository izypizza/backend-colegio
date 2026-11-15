<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CLAVES_SIN_USO = [
        'proteger_grados_secciones',
        'seguridad_proteccion_grados',
        'seguridad_proteccion_secciones',
    ];

    /**
     * Elimina configuraciones que nunca fueron leídas por el sistema.
     *
     * - proteger_grados_secciones duplicaba el concepto de las dos siguientes
     *   y se creó desde la interfaz.
     * - seguridad_proteccion_grados y seguridad_proteccion_secciones nunca se
     *   consultaron: la confirmación ya la hace la pantalla en cada acción.
     * - las claves general_* no se mostraban ni se usaban; el nombre de la
     *   institución está definido en la configuración de la aplicación.
     *
     * Se conserva seguridad_intentos_login, que ahora sí se aplica al iniciar
     * sesión.
     */
    public function up(): void
    {
        DB::table('configuraciones')->whereIn('clave', self::CLAVES_SIN_USO)->delete();
        DB::table('configuraciones')->where('clave', 'like', 'general%')->delete();
    }

    /**
     * No se restauran: eran valores sin efecto en el sistema.
     */
    public function down(): void
    {
    }
};
