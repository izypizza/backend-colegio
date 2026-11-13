<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ata cada calificación y cada asistencia a la matrícula en la que ocurrieron.
     * Así el historial conserva su sección y su año aunque el estudiante cambie.
     */
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->foreignId('matricula_id')
                ->nullable()
                ->after('estudiante_id')
                ->constrained('matriculas')
                ->nullOnDelete();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('matricula_id')
                ->nullable()
                ->after('estudiante_id')
                ->constrained('matriculas')
                ->nullOnDelete();
        });

        $this->rellenarCalificaciones();
        $this->rellenarAsistencias();
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matricula_id');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matricula_id');
        });
    }

    public function rellenarCalificaciones(): void
    {
        $matriculas = $this->mapaDeMatriculas();
        $anioPorAnioEscolar = $this->anioIdPorAnio();

        if (empty($matriculas) || empty($anioPorAnioEscolar)) {
            return;
        }

        $anioPorPeriodo = [];
        foreach (DB::table('periodos_academicos')->select('id', 'anio')->get() as $periodo) {
            $anioPorPeriodo[$periodo->id] = (int) $periodo->anio;
        }

        $grupos = [];

        DB::table('calificaciones')
            ->whereNull('matricula_id')
            ->select('id', 'estudiante_id', 'periodo_academico_id')
            ->orderBy('id')
            ->chunk(1000, function ($filas) use (&$grupos, $anioPorPeriodo, $anioPorAnioEscolar, $matriculas) {
                foreach ($filas as $fila) {
                    $anio = $anioPorPeriodo[$fila->periodo_academico_id] ?? null;
                    $anioEscolarId = $anio ? ($anioPorAnioEscolar[$anio] ?? null) : null;
                    $matriculaId = $anioEscolarId
                        ? ($matriculas[$fila->estudiante_id . ':' . $anioEscolarId] ?? null)
                        : null;

                    if ($matriculaId) {
                        $grupos[$matriculaId][] = $fila->id;
                    }
                }
            });

        $this->aplicarGrupos('calificaciones', $grupos);
    }

    public function rellenarAsistencias(): void
    {
        $matriculas = $this->mapaDeMatriculas();
        $anioPorAnioEscolar = $this->anioIdPorAnio();

        if (empty($matriculas) || empty($anioPorAnioEscolar)) {
            return;
        }

        $grupos = [];

        DB::table('asistencias')
            ->whereNull('matricula_id')
            ->select('id', 'estudiante_id', 'fecha')
            ->orderBy('id')
            ->chunk(1000, function ($filas) use (&$grupos, $anioPorAnioEscolar, $matriculas) {
                foreach ($filas as $fila) {
                    $anio = (int) substr((string) $fila->fecha, 0, 4);
                    $anioEscolarId = $anioPorAnioEscolar[$anio] ?? null;
                    $matriculaId = $anioEscolarId
                        ? ($matriculas[$fila->estudiante_id . ':' . $anioEscolarId] ?? null)
                        : null;

                    if ($matriculaId) {
                        $grupos[$matriculaId][] = $fila->id;
                    }
                }
            });

        $this->aplicarGrupos('asistencias', $grupos);
    }

    /**
     * @param  array<int, array<int, int>>  $grupos
     */
    private function aplicarGrupos(string $tabla, array $grupos): void
    {
        foreach ($grupos as $matriculaId => $ids) {
            foreach (array_chunk($ids, 500) as $lote) {
                DB::table($tabla)
                    ->whereIn('id', $lote)
                    ->update(['matricula_id' => $matriculaId]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function mapaDeMatriculas(): array
    {
        $mapa = [];

        DB::table('matriculas')
            ->select('id', 'estudiante_id', 'anio_escolar_id')
            ->orderBy('id')
            ->each(function ($matricula) use (&$mapa) {
                $clave = $matricula->estudiante_id . ':' . $matricula->anio_escolar_id;

                if (!isset($mapa[$clave])) {
                    $mapa[$clave] = $matricula->id;
                }
            });

        return $mapa;
    }

    /**
     * @return array<int, int>
     */
    private function anioIdPorAnio(): array
    {
        $mapa = [];

        foreach (DB::table('anios_escolares')->select('id', 'anio')->get() as $anioEscolar) {
            $mapa[(int) $anioEscolar->anio] = $anioEscolar->id;
        }

        return $mapa;
    }
};
