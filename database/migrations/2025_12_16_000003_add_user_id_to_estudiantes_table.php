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
            if (!Schema::hasColumn('estudiantes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            }

            if (!Schema::hasColumn('estudiantes', 'dni')) {
                $table->string('dni', 8)->nullable()->unique();
            }
            if (!Schema::hasColumn('estudiantes', 'fecha_nacimiento')) {
                $table->date('fecha_nacimiento')->nullable();
            }
            if (!Schema::hasColumn('estudiantes', 'direccion')) {
                $table->string('direccion')->nullable();
            }
            if (!Schema::hasColumn('estudiantes', 'telefono')) {
                $table->string('telefono')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (Schema::hasColumn('estudiantes', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            foreach (['dni', 'fecha_nacimiento', 'direccion', 'telefono'] as $column) {
                if (Schema::hasColumn('estudiantes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
