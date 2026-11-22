<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las materias no se eliminan: se desactivan para dejar de ofrecerse
     * sin perder calificaciones, horarios ni asignaciones históricas.
     */
    public function up(): void
    {
        if (Schema::hasColumn('materias', 'activo')) {
            return;
        }

        Schema::table('materias', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('nombre');
        });

        DB::table('materias')->update(['activo' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('materias', 'activo')) {
            return;
        }

        Schema::table('materias', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
