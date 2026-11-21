<?php

namespace App\Services;

use App\Helpers\CnebHelper;
use App\Models\ActividadEvaluacion;
use App\Models\AnioEscolar;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\NotaActividad;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionActividadesService
{
    /**
     * Guarda las notas de una evidencia y recalcula la competencia.
     *
     * @param  array<int, array{estudiante_id: int, nota?: mixed, observacion?: ?string}>  $registros
     */
    public function registrarNotas(ActividadEvaluacion $actividad, array $registros, ?User $usuario = null): array
    {
        $this->asegurarPeriodoEditable((int) $actividad->periodo_academico_id);

        $actividad->loadMissing('competencia');

        if ($registros === []) {
            throw ValidationException::withMessages([
                'registros' => 'No hay notas para registrar.',
            ]);
        }

        $ids = collect($registros)->pluck('estudiante_id')->map(fn ($id) => (int) $id)->unique();
        $estudiantes = Estudiante::query()->with('seccion.grado')->whereIn('id', $ids)->get()->keyBy('id');

        if ($estudiantes->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'registros' => 'Alguno de los estudiantes ya no existe.',
            ]);
        }

        $guardados = 0;

        DB::transaction(function () use ($actividad, $registros, $estudiantes, $usuario, &$guardados) {
            foreach ($registros as $indice => $registro) {
                $estudiante = $estudiantes->get((int) $registro['estudiante_id']);
                $this->asegurarCompetenciaDelCiclo($estudiante, $actividad->competencia);

                if (array_key_exists('nota', $registro) && $registro['nota'] !== null && $registro['nota'] !== '') {
                    $valor = (float) $registro['nota'];

                    if ($valor < 0 || $valor > 20) {
                        throw ValidationException::withMessages([
                            "registros.{$indice}.nota" => 'La nota debe estar entre 0 y 20.',
                        ]);
                    }
                }

                $nota = $registro['nota'] ?? null;
                $valorNota = ($nota === null || $nota === '') ? null : round((float) $nota, 2);

                NotaActividad::updateOrCreate(
                    [
                        'actividad_evaluacion_id' => $actividad->id,
                        'estudiante_id' => $estudiante->id,
                    ],
                    [
                        'nota' => $valorNota,
                        'observacion' => $registro['observacion'] ?? null,
                    ]
                );

                $this->recalcularCompetencia($actividad->competencia, $actividad->periodoAcademico, [$estudiante->id], $usuario);
                $guardados++;
            }
        });

        return ['guardados' => $guardados];
    }

    /**
     * Promedio ponderado de las evidencias con nota. Normaliza el peso
     * cuando las actividades no suman exactamente 100.
     */
    public function promedioPonderado(Estudiante $estudiante, Competencia $competencia, PeriodoAcademico $periodo): ?array
    {
        $actividades = ActividadEvaluacion::query()
            ->where('competencia_id', $competencia->id)
            ->where('periodo_academico_id', $periodo->id)
            ->get();

        if ($actividades->isEmpty()) {
            return null;
        }

        $notas = NotaActividad::query()
            ->whereIn('actividad_evaluacion_id', $actividades->pluck('id'))
            ->where('estudiante_id', $estudiante->id)
            ->get()
            ->keyBy('actividad_evaluacion_id');

        $sumaPonderada = 0.0;
        $sumaPesos = 0.0;
        $detalle = [];

        foreach ($actividades as $actividad) {
            $registro = $notas->get($actividad->id);
            $peso = (float) $actividad->peso;

            if (! $registro || $registro->nota === null || $peso <= 0) {
                continue;
            }

            $sumaPonderada += (float) $registro->nota * $peso;
            $sumaPesos += $peso;

            $detalle[] = [
                'actividad_id' => $actividad->id,
                'nombre' => $actividad->nombre,
                'tipo' => $actividad->tipo,
                'peso' => $peso,
                'nota' => (float) $registro->nota,
                'nivel' => $registro->nivel,
            ];
        }

        if ($sumaPesos <= 0) {
            return null;
        }

        $promedio = round($sumaPonderada / $sumaPesos, 2);

        return [
            'promedio' => $promedio,
            'nivel' => CnebHelper::nivelDesdeNota($promedio),
            'peso_total' => round($sumaPesos, 2),
            'actividades' => $detalle,
        ];
    }

    /**
     * Actualiza la nota de la competencia a partir de sus evidencias.
     *
     * @param  array<int, int>|null  $estudianteIds
     */
    public function recalcularCompetencia(
        Competencia $competencia,
        PeriodoAcademico $periodo,
        ?array $estudianteIds = null,
        ?User $usuario = null
    ): int {
        $this->asegurarPeriodoEditable((int) $periodo->id);

        $ids = $estudianteIds ?: $this->estudiantesConNotas($competencia, $periodo);
        $actualizados = 0;

        foreach ($ids as $estudianteId) {
            $estudiante = Estudiante::with('seccion.grado')->find($estudianteId);

            if (! $estudiante) {
                continue;
            }

            $resultado = $this->promedioPonderado($estudiante, $competencia, $periodo);

            if ($resultado === null) {
                continue;
            }

            CalificacionCompetencia::updateOrCreate(
                [
                    'estudiante_id' => $estudiante->id,
                    'competencia_id' => $competencia->id,
                    'periodo_academico_id' => $periodo->id,
                ],
                [
                    'escala' => 'numerica',
                    'nota' => $resultado['promedio'],
                    'nivel' => $resultado['nivel'],
                    'calculada_desde_actividades' => true,
                    'registrado_por' => $usuario?->id,
                ]
            );

            $actualizados++;
        }

        return $actualizados;
    }

    /**
     * Notas de una evidencia agrupadas por estudiante.
     */
    public function notasPorEstudiante(ActividadEvaluacion $actividad): Collection
    {
        return $actividad->notas()->get()->keyBy('estudiante_id');
    }

    /**
     * @return array<int, int>
     */
    private function estudiantesConNotas(Competencia $competencia, PeriodoAcademico $periodo): array
    {
        $actividades = ActividadEvaluacion::query()
            ->where('competencia_id', $competencia->id)
            ->where('periodo_academico_id', $periodo->id)
            ->pluck('id');

        if ($actividades->isEmpty()) {
            return [];
        }

        return NotaActividad::query()
            ->whereIn('actividad_evaluacion_id', $actividades)
            ->whereNotNull('nota')
            ->pluck('estudiante_id')
            ->unique()
            ->values()
            ->all();
    }

    private function asegurarCompetenciaDelCiclo(Estudiante $estudiante, ?Competencia $competencia): void
    {
        $ciclo = $estudiante->ciclo();

        if ($competencia && $ciclo && $competencia->ciclo !== $ciclo) {
            throw ValidationException::withMessages([
                'competencia_id' => 'La competencia no corresponde al ciclo del estudiante.',
            ]);
        }
    }

    private function asegurarPeriodoEditable(int $periodoId): void
    {
        $anio = PeriodoAcademico::whereKey($periodoId)->value('anio');

        if (! $anio) {
            return;
        }

        $cerrado = AnioEscolar::query()
            ->where('anio', $anio)
            ->where('estado', 'cerrado')
            ->exists();

        if ($cerrado) {
            throw ValidationException::withMessages([
                'periodo_academico_id' => 'El año escolar está cerrado. Solicita la reapertura para modificar notas.',
            ]);
        }
    }
}
