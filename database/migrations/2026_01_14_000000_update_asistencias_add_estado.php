<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            // Agregar columna estado después de fecha
            $table->enum('estado', ['presente', 'tarde', 'ausente'])->default('presente')->after('fecha');
            $table->text('observaciones')->nullable()->after('estado');
        });

        // Migrar datos existentes: convertir 'presente' boolean a 'estado' enum
        DB::statement("
            UPDATE asistencias 
            SET estado = CASE 
                WHEN presente = 1 THEN 'presente'
                WHEN presente = 0 THEN 'ausente'
            END
        ");

        // Eliminar la columna 'presente' antigua
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('presente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            // Restaurar columna presente
            $table->boolean('presente')->default(true)->after('fecha');
        });

        // Migrar datos de regreso
        DB::statement("
            UPDATE asistencias 
            SET presente = CASE 
                WHEN estado IN ('presente', 'tarde') THEN 1
                ELSE 0
            END
        ");

        // Eliminar columnas nuevas
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn(['estado', 'observaciones']);
        });
    }
};
