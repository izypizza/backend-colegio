<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra quién es apoderado en cada matrícula.
     *
     * La tabla estudiante_padre se conserva como vínculo histórico general,
     * de modo que el portal del padre y las consultas existentes siguen
     * funcionando igual.
     */
    public function up(): void
    {
        Schema::create('matricula_apoderado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();
            $table->foreignId('padre_id')
                ->constrained('padres')
                ->cascadeOnDelete();
            $table->enum('tipo', ['padre', 'madre', 'apoderado'])->default('apoderado');
            $table->boolean('es_contacto_principal')->default(false);
            $table->timestamps();

            $table->unique(['matricula_id', 'padre_id'], 'matricula_apoderado_unique');
        });

        $this->trasladarApoderadosExistentes();
    }

    public function down(): void
    {
        Schema::dropIfExists('matricula_apoderado');
    }

    /**
     * Copia los apoderados ya registrados a la matrícula correspondiente.
     */
    public function trasladarApoderadosExistentes(): void
    {
        DB::table('matriculas')
            ->select('id', 'estudiante_id')
            ->orderBy('id')
            ->chunk(500, function ($matriculas) {
                $vinculos = DB::table('estudiante_padre')
                    ->whereIn('estudiante_id', $matriculas->pluck('estudiante_id'))
                    ->get()
                    ->groupBy('estudiante_id');

                $filas = [];

                foreach ($matriculas as $matricula) {
                    foreach ($vinculos[$matricula->estudiante_id] ?? [] as $vinculo) {
                        $filas[] = [
                            'matricula_id' => $matricula->id,
                            'padre_id' => $vinculo->padre_id,
                            'tipo' => 'apoderado',
                            'es_contacto_principal' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($filas)) {
                    DB::table('matricula_apoderado')->insertOrIgnore($filas);
                }
            });
    }
};
