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
        Schema::table('estudiantes', function (Blueprint $table) {
            // Agregar columna user_id nullable (opcional, para que estudiantes puedan ver sus notas)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            
            // Agregar campos adicionales útiles si no existen
            if (!Schema::hasColumn('estudiantes', 'dni')) {
                $table->string('dni', 8)->nullable()->unique()->after('nombre');
            }
            if (!Schema::hasColumn('estudiantes', 'fecha_nacimiento')) {
                $table->date('fecha_nacimiento')->nullable()->after('dni');
            }
            if (!Schema::hasColumn('estudiantes', 'direccion')) {
                $table->text('direccion')->nullable()->after('fecha_nacimiento');
            }
            if (!Schema::hasColumn('estudiantes', 'telefono')) {
                $table->string('telefono')->nullable()->after('direccion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id']);
            
            // Solo eliminar si las columnas existen
            if (Schema::hasColumn('estudiantes', 'dni')) {
                $table->dropColumn('dni');
            }
            if (Schema::hasColumn('estudiantes', 'fecha_nacimiento')) {
                $table->dropColumn('fecha_nacimiento');
            }
            if (Schema::hasColumn('estudiantes', 'direccion')) {
                $table->dropColumn('direccion');
            }
            if (Schema::hasColumn('estudiantes', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }
};
