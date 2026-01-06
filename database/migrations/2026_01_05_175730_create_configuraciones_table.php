<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique()->comment('Clave única de la configuración');
            $table->text('valor')->nullable()->comment('Valor de la configuración');
            $table->string('tipo')->default('string')->comment('Tipo de dato: string, boolean, integer, json');
            $table->text('descripcion')->nullable()->comment('Descripción de la configuración');
            $table->string('categoria')->default('general')->comment('Categoría: general, modulos, sistema, seguridad');
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('configuraciones')->insert([
            [
                'clave' => 'modulo_calificaciones_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de calificaciones en todo el sistema',
                'categoria' => 'modulos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'proteger_grados_secciones',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Protege la edición y eliminación de grados y secciones (solo admin)',
                'categoria' => 'seguridad',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'modulo_biblioteca_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de biblioteca',
                'categoria' => 'modulos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'modulo_elecciones_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de elecciones',
                'categoria' => 'modulos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'nombre_institucion',
                'valor' => 'Institución Educativa',
                'tipo' => 'string',
                'descripcion' => 'Nombre de la institución educativa',
                'categoria' => 'general',
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
        Schema::dropIfExists('configuraciones');
    }
};
