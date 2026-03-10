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
        Schema::table('padres', function (Blueprint $table) {
            if (!Schema::hasColumn('padres', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            }

            if (!Schema::hasColumn('padres', 'email')) {
                $table->string('email')->nullable()->unique();
            }
            if (!Schema::hasColumn('padres', 'dni')) {
                $table->string('dni', 8)->nullable()->unique();
            }
            if (!Schema::hasColumn('padres', 'direccion')) {
                $table->string('direccion')->nullable();
            }
            if (!Schema::hasColumn('padres', 'ocupacion')) {
                $table->string('ocupacion')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            if (Schema::hasColumn('padres', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            foreach (['email', 'dni', 'direccion', 'ocupacion'] as $column) {
                if (Schema::hasColumn('padres', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
