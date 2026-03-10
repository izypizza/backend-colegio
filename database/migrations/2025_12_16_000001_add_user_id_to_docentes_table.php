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
            if (!Schema::hasColumn('docentes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            }

            if (!Schema::hasColumn('docentes', 'email')) {
                $table->string('email')->nullable()->unique();
            }
            if (!Schema::hasColumn('docentes', 'telefono')) {
                $table->string('telefono')->nullable();
            }
            if (!Schema::hasColumn('docentes', 'dni')) {
                $table->string('dni', 8)->nullable()->unique();
            }
            if (!Schema::hasColumn('docentes', 'direccion')) {
                $table->string('direccion')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            if (Schema::hasColumn('docentes', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            foreach (['email', 'telefono', 'dni', 'direccion'] as $column) {
                if (Schema::hasColumn('docentes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
