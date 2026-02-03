<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('chat_conversaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('mensaje');
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();

            $table->index(['conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mensajes');
    }
};
