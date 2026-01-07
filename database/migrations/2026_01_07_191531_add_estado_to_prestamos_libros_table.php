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
        Schema::table('prestamos_libros', function (Blueprint $table) {
            // Estados: pendiente, aprobado, rechazado, devuelto
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'devuelto'])
                  ->default('pendiente')
                  ->after('devuelto');
            
            // Usuario que aprobó/rechazó (bibliotecario o admin)
            $table->foreignId('aprobado_por')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            
            // Fecha y motivo de aprobación/rechazo
            $table->timestamp('fecha_respuesta')->nullable()->after('aprobado_por');
            $table->text('motivo_rechazo')->nullable()->after('fecha_respuesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_libros', function (Blueprint $table) {
            $table->dropForeign(['aprobado_por']);
            $table->dropColumn(['estado', 'aprobado_por', 'fecha_respuesta', 'motivo_rechazo']);
        });
    }
};
