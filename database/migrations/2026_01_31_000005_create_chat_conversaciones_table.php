<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('padre_id')->constrained('padres')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->timestamps();

            $table->unique(['docente_id', 'padre_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversaciones');
    }
};
