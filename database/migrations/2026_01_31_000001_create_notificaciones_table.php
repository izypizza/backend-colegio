<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('tipo')->default('info');
            $table->json('data')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leido_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
