<?php

namespace App\Http\Controllers;

use App\Models\ActividadEvaluacion;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use App\Services\EvaluacionActividadesService;
use App\Traits\VerificaAutorizacionDocente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActividadEvaluacionController extends Controller
{
    use VerificaAutorizacionDocente;

    public function __construct(private EvaluacionActividadesService $evaluaciones)
    {
    }

    /**
     * Evidencias de una competencia en un periodo, con sus notas.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'competencia_id' => 'required|exists:competencias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'seccion_id' => 'nullable|exists:secciones,id',
        ]);

        $competencia = Competencia::with('materia:id,nombre')->findOrFail($validated['competencia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $competencia->materia_id,
            $validated['seccion_id'] ?? null,
            (int) $periodo->id
        );
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $actividades = ActividadEvaluacion::query()
            ->where('competencia_id', $competencia->id)
            ->where('periodo_academico_id', $periodo->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $notas = $actividades->flatMap(fn (ActividadEvaluacion $actividad) => $actividad->notas()->get())
            ->groupBy('estudiante_id')
            ->map(fn ($registros) => $registros->keyBy('actividad_evaluacion_id'));

        return response()->json([
            'competencia' => [
                'id' => $competencia->id,
                'codigo' => $competencia->codigo,
                'nombre' => $competencia->nombre,
                'materia' => $competencia->materia?->nombre,
                'ciclo' => $competencia->ciclo,
            ],
            'actividades' => $actividades,
            'peso_total' => round((float) $actividades->sum('peso'), 2),
            'notas' => $notas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $competencia = Competencia::findOrFail($validated['competencia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $competencia->materia_id,
            null,
            (int) $periodo->id
        );
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $actividad = ActividadEvaluacion::create($validated);

        return response()->json([
            'message' => 'Evidencia registrada correctamente.',
            'actividad' => $actividad,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $actividad = ActividadEvaluacion::with('competencia')->findOrFail($id);
        $validated = $this->validar($request, $actividad->id);

        $autorizacion = $this->verificarDocenteAsignado((int) $actividad->competencia->materia_id, null, (int) $actividad->periodo_academico_id);
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $actividad->update($validated);

        return response()->json([
            'message' => 'Evidencia actualizada correctamente.',
            'actividad' => $actividad->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $actividad = ActividadEvaluacion::with('competencia')->findOrFail($id);

        $autorizacion = $this->verificarDocenteAsignado((int) $actividad->competencia->materia_id, null, (int) $actividad->periodo_academico_id);
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $actividad->delete();

        return response()->json(['message' => 'Evidencia eliminada correctamente.']);
    }

    /**
     * Registro masivo de notas de una evidencia.
     */
    public function registrarNotas(Request $request, string $id)
    {
        $actividad = ActividadEvaluacion::with('competencia')->findOrFail($id);

        $autorizacion = $this->verificarDocenteAsignado((int) $actividad->competencia->materia_id, null, (int) $actividad->periodo_academico_id);
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $validated = $request->validate([
            'registros' => 'required|array|min:1|max:200',
            'registros.*.estudiante_id' => 'required|exists:estudiantes,id',
            'registros.*.nota' => 'nullable|numeric|min:0|max:20',
            'registros.*.observacion' => 'nullable|string|max:500',
        ]);

        $resultado = $this->evaluaciones->registrarNotas($actividad, $validated['registros'], $request->user());

        return response()->json([
            'message' => "Se registraron {$resultado['guardados']} notas.",
            'guardados' => $resultado['guardados'],
        ], 201);
    }

    /**
     * Recalcular la nota de competencia de toda la sección.
     */
    public function recalcular(Request $request)
    {
        $validated = $request->validate([
            'competencia_id' => 'required|exists:competencias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
        ]);

        $competencia = Competencia::findOrFail($validated['competencia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);

        $autorizacion = $this->verificarDocenteAsignado((int) $competencia->materia_id, null, (int) $periodo->id);
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $actualizados = $this->evaluaciones->recalcularCompetencia($competencia, $periodo, null, $request->user());

        return response()->json([
            'message' => "Se recalcularon {$actualizados} calificaciones de competencia.",
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * Detalle del cálculo para un estudiante.
     */
    public function resumen(Request $request)
    {
        $validated = $request->validate([
            'competencia_id' => 'required|exists:competencias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'estudiante_id' => 'required|exists:estudiantes,id',
        ]);

        $competencia = Competencia::findOrFail($validated['competencia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);
        $estudiante = Estudiante::with('seccion.grado')->findOrFail($validated['estudiante_id']);

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $competencia->materia_id,
            (int) $estudiante->seccion_id,
            (int) $periodo->id
        );
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        return response()->json([
            'estudiante' => [
                'id' => $estudiante->id,
                'nombre_completo' => $estudiante->nombre_completo,
            ],
            'resultado' => $this->evaluaciones->promedioPonderado($estudiante, $competencia, $periodo),
        ]);
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'competencia_id' => 'required|exists:competencias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('actividades_evaluacion', 'nombre')
                    ->where('competencia_id', $request->input('competencia_id'))
                    ->where('periodo_academico_id', $request->input('periodo_academico_id'))
                    ->ignore($ignorarId),
            ],
            'tipo' => ['required', Rule::in(ActividadEvaluacion::TIPOS)],
            'peso' => 'required|numeric|min:0.01|max:100',
            'fecha' => 'nullable|date',
            'orden' => 'nullable|integer|min:0|max:99',
        ], [
            'nombre.unique' => 'Ya existe una evidencia con ese nombre en esta competencia y periodo.',
            'peso.min' => 'El peso debe ser mayor que cero.',
            'tipo.in' => 'El tipo debe ser examen, tarea, práctica, proyecto, participación u otro.',
        ]);
    }
}
