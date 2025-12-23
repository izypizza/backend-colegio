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
        Schema::table('docentes', function (Blueprint $table) {
            // Agregar columna user_id nullable (por si ya hay datos)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');

            // Agregar campos adicionales útiles
            $table->string('email')->nullable()->after('nombre');
            $table->string('telefono')->nullable()->after('email');
            $table->string('dni', 8)->nullable()->unique()->after('telefono');
            $table->text('direccion')->nullable()->after('dni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'email', 'telefono', 'dni', 'direccion']);
        });
    }
};
