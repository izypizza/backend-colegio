<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices para los listados que ordenan por fecha de creación.
     * Evitan el filesort en tablas que crecen con el uso.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'idx_users_created_at')) {
                $table->index('created_at', 'idx_users_created_at');
            }
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_created_at')) {
                $table->index('created_at', 'idx_calificaciones_created_at');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! $this->indexExists('audit_logs', 'idx_audit_logs_created_at')) {
                $table->index('created_at', 'idx_audit_logs_created_at');
            }
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            if (! $this->indexExists('notificaciones', 'idx_notificaciones_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_notificaciones_user_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_created_at');
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_calificaciones_created_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_created_at');
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_notificaciones_user_created');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }
};
