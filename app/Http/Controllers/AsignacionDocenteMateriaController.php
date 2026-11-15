<?php

namespace App\Http\Controllers;

use App\Models\AnioEscolar;
use App\Models\AsignacionDocenteMateria;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionDocenteMateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asignaciones = AsignacionDocenteMateria::with(['docente', 'materia', 'seccion.grado', 'periodoAcademico'])->get();
        return response()->json($asignaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
            'seccion_id' => 'required|exists:secciones,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id'
        ]);

        $asignacion = AsignacionDocenteMateria::create($validated);
        return response()->json($asignacion->load(['docente', 'materia', 'seccion', 'periodoAcademico']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asignacion = AsignacionDocenteMateria::with(['docente', 'materia', 'seccion.grado', 'periodoAcademico'])->findOrFail($id);
        return response()->json($asignacion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $asignacion = AsignacionDocenteMateria::findOrFail($id);
        
        $validated = $request->validate([
            'docente_id' => 'sometimes|required|exists:docentes,id',
            'materia_id' => 'sometimes|required|exists:materias,id',
            'seccion_id' => 'sometimes|required|exists:secciones,id',
            'periodo_academico_id' => 'sometimes|required|exists:periodos_academicos,id'
        ]);

        $asignacion->update($validated);
        return response()->json($asignacion->load(['docente', 'materia', 'seccion', 'periodoAcademico']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asignacion = AsignacionDocenteMateria::findOrFail($id);
        $asignacion->delete();
        return response()->json(['message' => 'Asignación eliminada correctamente'], 200);
    }

    /**
     * Cursos asignados agrupados por docente, materia, sección y año,
     * indicando cuántos bimestres están cubiertos.
     */
    public function listadoAnual(Request $request)
    {
        $request->validate([
            'anio' => 'nullable|integer|min:2000|max:2100',
        ]);

        $anio = $request->filled('anio')
            ? (int) $request->input('anio')
            : (int) now()->year;

        $bimestresDelAnio = PeriodoAcademico::query()->where('anio', $anio)->count();
        $anioEscolarId = AnioEscolar::query()->delAnio($anio)->value('id') ?? 0;

        $filas = DB::table('asignacion_docente_materia as asignacion')
            ->join('periodos_academicos as periodo', 'periodo.id', '=', 'asignacion.periodo_academico_id')
            ->join('docentes as docente', 'docente.id', '=', 'asignacion.docente_id')
            ->join('materias as materia', 'materia.id', '=', 'asignacion.materia_id')
            ->join('secciones as seccion', 'seccion.id', '=', 'asignacion.seccion_id')
            ->join('grados as grado', 'grado.id', '=', 'seccion.grado_id')
            ->where('periodo.anio', $anio)
            ->selectRaw('
                asignacion.docente_id,
                docente.nombres,
                docente.apellido_paterno,
                docente.apellido_materno,
                asignacion.materia_id,
                materia.nombre as materia,
                asignacion.seccion_id,
                seccion.nombre as seccion,
                seccion.capacidad_maxima as capacidad,
                grado.nombre as grado,
                COUNT(*) as bimestres,
                MAX(asignacion.es_tutor) as es_tutor,
                (SELECT COUNT(*) FROM matriculas m
                    WHERE m.seccion_id = asignacion.seccion_id
                    AND m.anio_escolar_id = ?
                    AND m.estado = \'matriculado\') as ocupadas
            ', [$anioEscolarId])
            ->groupBy(
                'asignacion.docente_id',
                'docente.nombres',
                'docente.apellido_paterno',
                'docente.apellido_materno',
                'asignacion.materia_id',
                'materia.nombre',
                'asignacion.seccion_id',
                'seccion.nombre',
                'seccion.capacidad_maxima',
                'grado.nombre'
            )
            ->orderBy('docente.apellido_paterno')
            ->orderBy('grado.nombre')
            ->orderBy('seccion.nombre')
            ->get();

        $cursos = $filas->map(function ($fila) use ($anio, $bimestresDelAnio) {
            return [
                'id' => "{$fila->docente_id}-{$fila->materia_id}-{$fila->seccion_id}",
                'docente_id' => (int) $fila->docente_id,
                'docente' => trim("{$fila->apellido_paterno} {$fila->apellido_materno}, {$fila->nombres}"),
                'materia_id' => (int) $fila->materia_id,
                'materia' => $fila->materia,
                'seccion_id' => (int) $fila->seccion_id,
                'seccion' => $fila->seccion,
                'grado' => $fila->grado,
                'capacidad' => (int) $fila->capacidad,
                'ocupadas' => (int) $fila->ocupadas,
                'anio' => $anio,
                'bimestres' => (int) $fila->bimestres,
                'bimestres_del_anio' => $bimestresDelAnio,
                'es_tutor' => (bool) $fila->es_tutor,
            ];
        })->values();

        return response()->json([
            'anio' => $anio,
            'bimestres_del_anio' => $bimestresDelAnio,
            'cursos' => $cursos,
        ]);
    }

    /**
     * Asigna un curso a un docente en todos los bimestres del año de una vez.
     */
    public function asignarAnio(Request $request)
    {
        $validated = $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
            'seccion_id' => 'required|exists:secciones,id',
            'anio' => 'required|integer|min:2000|max:2100',
            'es_tutor' => 'nullable|boolean',
        ], [
            'docente_id.required' => 'Selecciona el docente.',
            'materia_id.required' => 'Selecciona la materia.',
            'seccion_id.required' => 'Selecciona la sección.',
            'anio.required' => 'Indica el año escolar.',
        ]);

        $periodos = PeriodoAcademico::query()
            ->where('anio', (int) $validated['anio'])
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get();

        if ($periodos->isEmpty()) {
            throw ValidationException::withMessages([
                'anio' => [
                    "El año {$validated['anio']} no tiene bimestres registrados. Genéralos primero en Periodos.",
                ],
            ]);
        }

        $creados = 0;
        $existentes = 0;

        DB::transaction(function () use ($validated, $periodos, &$creados, &$existentes) {
            foreach ($periodos as $periodo) {
                $asignacion = AsignacionDocenteMateria::firstOrCreate(
                    [
                        'docente_id' => $validated['docente_id'],
                        'materia_id' => $validated['materia_id'],
                        'seccion_id' => $validated['seccion_id'],
                        'periodo_academico_id' => $periodo->id,
                    ],
                    [
                        'es_tutor' => (bool) ($validated['es_tutor'] ?? false),
                    ]
                );

                $asignacion->wasRecentlyCreated ? $creados++ : $existentes++;
            }
        });

        return response()->json([
            'message' => $creados > 0
                ? "Curso asignado en {$creados} bimestre(s) del año {$validated['anio']}."
                : 'El curso ya estaba asignado en todos los bimestres.',
            'creados' => $creados,
            'existentes' => $existentes,
        ], $creados > 0 ? 201 : 200);
    }

    /**
     * Quita un curso del docente en todos los bimestres del año.
     */
    public function quitarAnio(Request $request)
    {
        $validated = $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
            'seccion_id' => 'required|exists:secciones,id',
            'anio' => 'required|integer|min:2000|max:2100',
        ]);

        $idsPeriodos = PeriodoAcademico::query()
            ->where('anio', (int) $validated['anio'])
            ->pluck('id');

        if ($idsPeriodos->isEmpty()) {
            throw ValidationException::withMessages([
                'anio' => ["El año {$validated['anio']} no tiene bimestres registrados."],
            ]);
        }

        $eliminadas = AsignacionDocenteMateria::query()
            ->where('docente_id', $validated['docente_id'])
            ->where('materia_id', $validated['materia_id'])
            ->where('seccion_id', $validated['seccion_id'])
            ->whereIn('periodo_academico_id', $idsPeriodos)
            ->delete();

        return response()->json([
            'message' => "Se quitaron {$eliminadas} asignación(es) del año {$validated['anio']}.",
            'eliminadas' => $eliminadas,
        ]);
    }
}
