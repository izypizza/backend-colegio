<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Vincula calificaciones y asistencias con la matrícula en la que ocurrieron.
 * Se usa en la migración, en el seeder y en el comando de reparación.
 */
class HistorialAcademicoService
{
    /**
     * @return array{calificaciones: int, asistencias: int}
     */
    public function vincularPendientes(): array
    {
        return [
            'calificaciones' => $this->vincularCalificaciones(),
            'asistencias' => $this->vincularAsistencias(),
        ];
    }

    public function vincularCalificaciones(): int
    {
        $matriculas = $this->mapaDeMatriculas();
        $anioPorAnioEscolar = $this->anioIdPorAnio();

        if (empty($matriculas) || empty($anioPorAnioEscolar)) {
            return 0;
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

        return $this->aplicarGrupos('calificaciones', $grupos);
    }

    public function vincularAsistencias(): int
    {
        $matriculas = $this->mapaDeMatriculas();
        $anioPorAnioEscolar = $this->anioIdPorAnio();

        if (empty($matriculas) || empty($anioPorAnioEscolar)) {
            return 0;
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

        return $this->aplicarGrupos('asistencias', $grupos);
    }

    /**
     * @param  array<int, array<int, int>>  $grupos
     */
    private function aplicarGrupos(string $tabla, array $grupos): int
    {
        $actualizadas = 0;

        foreach ($grupos as $matriculaId => $ids) {
            foreach (array_chunk($ids, 500) as $lote) {
                $actualizadas += DB::table($tabla)
                    ->whereIn('id', $lote)
                    ->update(['matricula_id' => $matriculaId]);
            }
        }

        return $actualizadas;
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
}
