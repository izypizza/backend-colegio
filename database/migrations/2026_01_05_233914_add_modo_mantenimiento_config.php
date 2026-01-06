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
        DB::table('configuraciones')->insert([
            [
                'clave' => 'sistema_modo_mantenimiento',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Activa el modo de mantenimiento del sistema',
                'categoria' => 'sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'sistema_mensaje_mantenimiento',
                'valor' => 'El sistema está en mantenimiento. Por favor, inténtelo más tarde.',
                'tipo' => 'string',
                'descripcion' => 'Mensaje personalizado para mostrar durante el mantenimiento',
                'categoria' => 'sistema',
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
        DB::table('configuraciones')->whereIn('clave', [
            'sistema_modo_mantenimiento',
            'sistema_mensaje_mantenimiento',
        ])->delete();
    }
};
