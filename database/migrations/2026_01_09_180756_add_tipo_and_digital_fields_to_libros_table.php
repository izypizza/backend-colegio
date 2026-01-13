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
        Schema::table('libros', function (Blueprint $table) {
            // Tipo de libro: fisico o digital (default fisico porque habrá más físicos)
            $table->enum('tipo', ['fisico', 'digital'])->default('fisico')->after('titulo');
            
            // URL/enlace para libros digitales (solo para digitales)
            $table->string('url_digital')->nullable()->after('cantidad_total');
            
            // Formato del libro digital (PDF, EPUB, etc.)
            $table->string('formato_digital')->nullable()->after('url_digital');
            
            // ISBN es más relevante para físicos (opcional)
            // Editorial es más para físicos (opcional)
            // Cantidad es solo para físicos - se puede calcular para digitales
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'url_digital', 'formato_digital']);
        });
    }
};
