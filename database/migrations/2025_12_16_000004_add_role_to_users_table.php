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
        Schema::table('users', function (Blueprint $table) {
            // Agregar columna de rol con auxiliar incluido
            $table->enum('role', ['admin', 'auxiliar', 'docente', 'padre', 'estudiante'])
                  ->default('estudiante')
                  ->after('email');
            
            // Agregar campos adicionales
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('avatar')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'avatar']);
        });
    }
};
