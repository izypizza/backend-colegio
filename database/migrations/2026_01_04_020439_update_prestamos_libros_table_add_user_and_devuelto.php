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
            // Agregar columna user_id si no existe
            if (!Schema::hasColumn('prestamos_libros', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('libro_id')->constrained('users')->cascadeOnDelete();
            }
            
            // Agregar columna devuelto
            if (!Schema::hasColumn('prestamos_libros', 'devuelto')) {
                $table->boolean('devuelto')->default(false)->after('fecha_devolucion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_libros', function (Blueprint $table) {
            if (Schema::hasColumn('prestamos_libros', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            
            if (Schema::hasColumn('prestamos_libros', 'devuelto')) {
                $table->dropColumn('devuelto');
            }
        });
    }
};
