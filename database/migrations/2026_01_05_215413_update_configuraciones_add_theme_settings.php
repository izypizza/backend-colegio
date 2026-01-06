<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar configuración de nombre_institucion
        DB::table('configuraciones')->where('clave', 'nombre_institucion')->delete();

        // Insertar configuraciones de accesibilidad (tamaño de fuente y lector de pantalla)
        DB::table('configuraciones')->insert([
            [
                'clave' => 'tema_tamano_fuente',
                'valor' => 'normal',
                'tipo' => 'string',
                'descripcion' => 'Tamaño de fuente del sistema: pequeño, normal, grande',
                'categoria' => 'accesibilidad',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'accesibilidad_lector_pantalla',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Optimiza la interfaz para lectores de pantalla',
                'categoria' => 'accesibilidad',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar configuraciones de accesibilidad
        DB::table('configuraciones')->whereIn('clave', [

            'tema_tamano_fuente',
            'accesibilidad_lector_pantalla',
        ])->delete();

        // Restaurar configuración de nombre_institucion
        DB::table('configuraciones')->insert([
            'clave' => 'nombre_institucion',
            'valor' => 'Institución Educativa',
            'tipo' => 'string',
            'descripcion' => 'Nombre de la institución educativa',
            'categoria' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
