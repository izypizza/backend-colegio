<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares_libros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->string('codigo', 100);
            $table->enum('estado', ['disponible', 'prestado', 'mantenimiento', 'perdido'])->default('disponible');
            $table->timestamps();

            $table->unique(['libro_id', 'codigo']);
        });

        Schema::table('prestamos_libros', function (Blueprint $table) {
            $table->foreignId('ejemplar_id')->nullable()->after('libro_id')
                ->constrained('ejemplares_libros')->nullOnDelete();
        });

        foreach (DB::table('libros')->orderBy('id')->get() as $libro) {
            $cantidad = max(1, (int) $libro->cantidad_total);

            for ($numero = 1; $numero <= $cantidad; $numero++) {
                DB::table('ejemplares_libros')->insert([
                    'libro_id' => $libro->id,
                    'codigo' => 'LIB-' . $libro->id . '-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                    'estado' => 'disponible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $prestamosActivos = DB::table('prestamos_libros')
            ->where('estado', 'aprobado')
            ->where('devuelto', false)
            ->orderBy('id')
            ->get();

        foreach ($prestamosActivos as $prestamo) {
            $ejemplar = DB::table('ejemplares_libros')
                ->where('libro_id', $prestamo->libro_id)
                ->where('estado', 'disponible')
                ->orderBy('id')
                ->first();

            if (! $ejemplar) continue;

            DB::table('ejemplares_libros')
                ->where('id', $ejemplar->id)
                ->update(['estado' => 'prestado', 'updated_at' => now()]);

            DB::table('prestamos_libros')
                ->where('id', $prestamo->id)
                ->update(['ejemplar_id' => $ejemplar->id]);
        }
    }

    public function down(): void
    {
        Schema::table('prestamos_libros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ejemplar_id');
        });

        Schema::dropIfExists('ejemplares_libros');
    }
};
