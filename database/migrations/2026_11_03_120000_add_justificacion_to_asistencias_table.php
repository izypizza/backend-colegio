<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->boolean('justificada')->default(false)->after('estado');
            $table->text('motivo_justificacion')->nullable()->after('justificada');
            $table->foreignId('justificado_por')->nullable()->after('motivo_justificacion')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('justificado_at')->nullable()->after('justificado_por');

            $table->index(['justificada', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex(['justificada', 'fecha']);
            $table->dropConstrainedForeignId('justificado_por');
            $table->dropColumn(['justificada', 'motivo_justificacion', 'justificado_at']);
        });
    }
};
