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
        // Crear tabla de permisos especiales para auxiliares
        Schema::create('auxiliar_permisos_especiales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('puede_editar_estudiantes')->default(false);
            $table->boolean('puede_editar_asistencias')->default(false);
            $table->boolean('puede_editar_calificaciones')->default(false);
            $table->timestamp('activado_hasta')->nullable(); // Fecha de expiración del permiso
            $table->string('activado_por')->nullable(); // Admin que activó el permiso
            $table->text('motivo')->nullable(); // Razón del permiso especial
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Agregar campos para elecciones con tiempo limitado
        Schema::table('elecciones', function (Blueprint $table) {
            $table->datetime('fecha_inicio')->nullable()->after('fecha');
            $table->datetime('fecha_cierre')->nullable()->after('fecha_inicio');
            $table->enum('estado', ['pendiente', 'activa', 'cerrada'])->default('pendiente')->after('fecha_cierre');
            $table->boolean('resultados_publicados')->default(false)->after('estado');
        });

        // Actualizar roles en users table para incluir BIBLIOTECARIO
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'docente', 'auxiliar', 'padre', 'estudiante', 'bibliotecario') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliar_permisos_especiales');
        
        Schema::table('elecciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'fecha_cierre', 'estado', 'resultados_publicados']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'docente', 'auxiliar', 'padre', 'estudiante') NOT NULL");
    }
};
