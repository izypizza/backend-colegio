<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mejorar tabla de notificaciones
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('prioridad')->default('normal')->after('tipo'); // alta, normal, baja
            $table->string('accion_url')->nullable()->after('data'); // URL a la que redirige
            $table->string('icono')->nullable()->after('accion_url'); // Icono para el frontend
        });

        // Mejorar tabla de chat_conversaciones
        Schema::table('chat_conversaciones', function (Blueprint $table) {
            $table->string('asunto')->nullable()->after('estudiante_id'); // Tema de la conversación
            $table->boolean('activa')->default(true)->after('asunto'); // Si está activa o archivada
        });

        // Mejorar tabla de chat_mensajes
        Schema::table('chat_mensajes', function (Blueprint $table) {
            $table->boolean('es_sistema')->default(false)->after('mensaje'); // Mensaje automático del sistema
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropColumn(['prioridad', 'accion_url', 'icono']);
        });

        Schema::table('chat_conversaciones', function (Blueprint $table) {
            $table->dropColumn(['asunto', 'activa']);
        });

        Schema::table('chat_mensajes', function (Blueprint $table) {
            $table->dropColumn('es_sistema');
        });
    }
};
