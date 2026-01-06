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
        Schema::table('secciones', function (Blueprint $table) {
            $table->foreignId('tutor_id')->nullable()->after('grado_id')->constrained('docentes')->nullOnDelete();
            $table->integer('capacidad_maxima')->default(40)->after('tutor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('secciones', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn(['tutor_id', 'capacidad_maxima']);
        });
    }
};
