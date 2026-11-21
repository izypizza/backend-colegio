<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDocenteMateria;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;

class EstudianteClasesController extends Controller
{
    /**
     * Cursos del estudiante con su docente, horario y competencias del área.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $estudiante = Estudiante::with('seccion.grado')->find($user->estudiante->id);

        $datosEstudiante = [
            'id' => $estudiante->id,
            'nombre_completo' => $estudiante->nombre_completo,
            'seccion' => $estudiante->seccion
                ? trim(($estudiante->seccion->grado->nombre ?? '') . ' - ' . $estudiante->seccion->nombre)
                : null,
            'ciclo' => $estudiante->ciclo(),
        ];

        if (! $estudiante->seccion_id) {
            return response()->json([
                'estudiante' => $datosEstudiante,
                'periodo' => null,
                'clases' => [],
            ]);
        }

        $periodo = PeriodoAcademico::where('estado', 'activo')->first();

        $asignaciones = AsignacionDocenteMateria::query()
            ->with([
                'materia:id,nombre',
                'docente:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->where('seccion_id', $estudiante->seccion_id)
            ->when($periodo, fn ($query) => $query->where('periodo_academico_id', $periodo->id))
            ->get();

        $horarios = Horario::query()
            ->where('seccion_id', $estudiante->seccion_id)
            ->get()
            ->groupBy('materia_id');

        $competencias = Competencia::query()
            ->activas()
            ->delCiclo($estudiante->ciclo())
            ->ordenadas()
            ->get()
            ->groupBy('materia_id');

        $misNotas = CalificacionCompetencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->when($periodo, fn ($query) => $query->where('periodo_academico_id', $periodo->id))
            ->get()
            ->keyBy('competencia_id');

        $clases = $asignaciones
            ->groupBy('materia_id')
            ->map(function ($grupo, $materiaId) use ($horarios, $competencias, $misNotas) {
                $primera = $grupo->first();
                $docente = $primera->docente;

                return [
                    'materia_id' => (int) $materiaId,
                    'materia' => $primera->materia?->nombre,
                    'docente' => $docente
                        ? trim("{$docente->apellido_paterno} {$docente->apellido_materno}, {$docente->nombres}")
                        : null,
                    'horario' => ($horarios->get($materiaId) ?? collect())
                        ->map(fn (Horario $bloque) => [
                            'dia' => $bloque->dia,
                            'hora_inicio' => $bloque->hora_inicio,
                            'hora_fin' => $bloque->hora_fin,
                        ])
                        ->values(),
                    'competencias' => ($competencias->get($materiaId) ?? collect())
                        ->map(fn (Competencia $competencia) => [
                            'id' => $competencia->id,
                            'codigo' => $competencia->codigo,
                            'nombre' => $competencia->nombre,
                            'nivel' => $misNotas->get($competencia->id)?->nivel,
                            'nota' => $misNotas->get($competencia->id)?->nota !== null
                                ? (float) $misNotas->get($competencia->id)->nota
                                : null,
                        ])
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'estudiante' => $datosEstudiante,
            'periodo' => $periodo ? [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre,
                'anio' => $periodo->anio,
            ] : null,
            'clases' => $clases,
        ]);
    }
}
