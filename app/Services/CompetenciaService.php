<?php

namespace App\Services;

use App\Helpers\CnebHelper;
use App\Models\AnioEscolar;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompetenciaService
{
    /**
     * Competencias activas del área y ciclo que cursa el estudiante.
     */
    public function competenciasDelEstudiante(Estudiante $estudiante, ?int $materiaId = null): Collection
    {
        $ciclo = $estudiante->ciclo();

        if (! $ciclo) {
            return collect();
        }

        return Competencia::query()
            ->activas()
            ->delCiclo($ciclo)
            ->when($materiaId, fn ($query) => $query->where('materia_id', $materiaId))
            ->with('materia:id,nombre')
            ->ordenadas()
            ->get();
    }

    /**
     * Guarda la valoración de una competencia respetando la escala elegida.
     *
     * @param  array{nivel?: ?string, nota?: mixed, escala?: ?string, conclusion_descriptiva?: ?string}  $datos
     */
    public function registrar(
        Estudiante $estudiante,
        Competencia $competencia,
        PeriodoAcademico $periodo,
        array $datos,
        ?User $usuario = null
    ): CalificacionCompetencia {
        $this->asegurarPeriodoEditable($periodo);

        $escala = $datos['escala'] ?? 'literal';
        if (! in_array($escala, CnebHelper::ESCALAS, true)) {
            throw ValidationException::withMessages([
                'escala' => 'La escala debe ser literal o numérica.',
            ]);
        }

        $nivel = null;
        $nota = null;

        if ($escala === 'literal') {
            $nivel = CnebHelper::normalizarNivel($datos['nivel'] ?? null);

            if ($nivel === null) {
                throw ValidationException::withMessages([
                    'nivel' => 'Selecciona un nivel de logro válido: AD, A, B o C.',
                ]);
            }
        } else {
            $valor = $datos['nota'] ?? null;

            if (! is_numeric($valor) || $valor < 0 || $valor > 20) {
                throw ValidationException::withMessages([
                    'nota' => 'La nota debe estar entre 0 y 20.',
                ]);
            }

            $nota = round((float) $valor, 2);
            $nivel = CnebHelper::nivelDesdeNota($nota);
        }

        $conclusion = trim((string) ($datos['conclusion_descriptiva'] ?? ''));

        if ($conclusion === '' && CnebHelper::requiereConclusionDescriptiva($nivel)) {
            throw ValidationException::withMessages([
                'conclusion_descriptiva' => 'Los niveles B y C requieren una conclusión descriptiva que explique la dificultad y cómo superarla.',
            ]);
        }

        $existente = CalificacionCompetencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('competencia_id', $competencia->id)
            ->where('periodo_academico_id', $periodo->id)
            ->first();

        if ($existente && $this->esDocente($usuario)) {
            $cambio = $existente->nivel !== $nivel || (float) $existente->nota !== (float) $nota;

            if ($cambio && $existente->modificaciones_count >= 3) {
                throw ValidationException::withMessages([
                    'nivel' => 'Esta competencia ya fue modificada 3 veces. Solicita el cambio al administrador.',
                ]);
            }

            if ($cambio) {
                $existente->modificaciones_count += 1;
                $existente->ultima_modificacion = now();
            }
        }

        $atributos = [
            'escala' => $escala,
            'nivel' => $nivel,
            'nota' => $escala === 'numerica' ? $nota : null,
            'conclusion_descriptiva' => $conclusion !== '' ? $conclusion : null,
            'registrado_por' => $usuario?->id,
        ];

        if ($existente) {
            $existente->fill($atributos);
            $existente->save();

            return $existente->fresh(['competencia.materia', 'periodoAcademico']);
        }

        return CalificacionCompetencia::create($atributos + [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
        ])->load(['competencia.materia', 'periodoAcademico']);
    }

    /**
     * Consolidado del periodo: cada competencia conserva su nivel.
     * No se calcula promedio de área.
     */
    public function consolidadoDelPeriodo(Estudiante $estudiante, PeriodoAcademico $periodo): array
    {
        $competencias = $this->competenciasDelEstudiante($estudiante)
            ->groupBy('materia_id');

        $calificaciones = CalificacionCompetencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('periodo_academico_id', $periodo->id)
            ->get()
            ->keyBy('competencia_id');

        $areas = $competencias->map(function (Collection $competenciasDelArea, $materiaId) use ($calificaciones) {
            $items = $competenciasDelArea->map(function (Competencia $competencia) use ($calificaciones) {
                $registro = $calificaciones->get($competencia->id);

                return [
                    'competencia_id' => $competencia->id,
                    'codigo' => $competencia->codigo,
                    'nombre' => $competencia->nombre,
                    'nivel' => $registro?->nivel,
                    'nota' => $registro?->nota !== null ? (float) $registro->nota : null,
                    'escala' => $registro?->escala,
                    'conclusion_descriptiva' => $registro?->conclusion_descriptiva,
                    'registrada' => $registro !== null,
                ];
            })->values();

            return [
                'materia_id' => (int) $materiaId,
                'materia' => $competenciasDelArea->first()->materia?->nombre,
                'ciclo' => $competenciasDelArea->first()->ciclo,
                'competencias' => $items,
                'requiere_conclusion' => $items->contains(fn ($item) => CnebHelper::requiereConclusionDescriptiva($item['nivel'])),
                'completas' => $items->every(fn ($item) => $item['registrada']),
            ];
        })->values();

        return [
            'estudiante' => [
                'id' => $estudiante->id,
                'nombre_completo' => $estudiante->nombre_completo,
                'ciclo' => $estudiante->ciclo(),
            ],
            'periodo' => [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre,
                'anio' => $periodo->anio,
            ],
            'areas' => $areas,
        ];
    }

    private function asegurarPeriodoEditable(PeriodoAcademico $periodo): void
    {
        $cerrado = AnioEscolar::query()
            ->where('anio', $periodo->anio)
            ->where('estado', 'cerrado')
            ->exists();

        if ($cerrado) {
            throw ValidationException::withMessages([
                'periodo_academico_id' => 'El año escolar está cerrado. Solicita la reapertura para modificar calificaciones.',
            ]);
        }
    }

    private function esDocente(?User $usuario): bool
    {
        return $usuario !== null && $usuario->isDocente();
    }
}
