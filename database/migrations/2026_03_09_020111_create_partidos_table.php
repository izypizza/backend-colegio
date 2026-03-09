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
        // Crear tabla de partidos
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleccion_id')->constrained('elecciones')->onDelete('cascade');
            $table->string('nombre')->comment('Nombre del partido político');
            $table->string('siglas', 20)->comment('Siglas del partido (ej: PAP, PME)');
            $table->text('descripcion')->nullable()->comment('Descripción o propuestas del partido');
            $table->string('logo')->nullable()->comment('URL o path del logo del partido');
            $table->string('color', 7)->default('#3B82F6')->comment('Color hexadecimal para UI');
            $table->timestamps();
        });

        // Agregar partido_id a candidatos
        Schema::table('candidatos', function (Blueprint $table) {
            $table->foreignId('partido_id')->nullable()->after('eleccion_id')->constrained('partidos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->dropForeign(['partido_id']);
            $table->dropColumn('partido_id');
        });
        
        Schema::dropIfExists('partidos');
    }
};
