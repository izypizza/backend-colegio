<?php

namespace App\Http\Controllers;

use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use App\Services\CompetenciaService;
use App\Traits\VerificaAutorizacionDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalificacionCompetenciaController extends Controller
{
    use VerificaAutorizacionDocente;

    public function __construct(private CompetenciaService $competencias)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'materia_id' => 'nullable|exists:materias,id',
            'competencia_id' => 'nullable|exists:competencias,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $query = CalificacionCompetencia::query()
            ->visiblePara($request->user())
            ->with(['competencia:id,materia_id,ciclo,codigo,nombre', 'competencia.materia:id,nombre', 'periodoAcademico:id,nombre,anio'])
            ->when($request->filled('estudiante_id'), fn ($q) => $q->where('estudiante_id', $request->input('estudiante_id')))
            ->when($request->filled('competencia_id'), fn ($q) => $q->where('competencia_id', $request->input('competencia_id')))
            ->when($request->filled('periodo_academico_id'), fn ($q) => $q->where('periodo_academico_id', $request->input('periodo_academico_id')))
            ->when($request->filled('materia_id'), fn ($q) => $q->whereHas('competencia', fn ($c) => $c->where('materia_id', $request->input('materia_id'))))
            ->orderByDesc('id');

        return response()->json($query->paginate($request->integer('per_page', 50)));
    }

    /**
     * Consolidado del periodo: una letra por competencia, sin promedio de área.
     */
    public function consolidado(Request $request, string $estudianteId, string $periodoId)
    {
        $estudiante = Estudiante::with('seccion.grado')->findOrFail($estudianteId);
        $periodo = PeriodoAcademico::findOrFail($periodoId);

        $autorizacion = $this->autorizarLectura($request, $estudiante);
        if ($autorizacion !== null) {
            return $autorizacion;
        }

        return response()->json(
            $this->competencias->consolidadoDelPeriodo($estudiante, $periodo)
        );
    }

    public function guardar(Request $request)
    {
        $this->normalizarNivel($request);

        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'competencia_id' => 'required|exists:competencias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'escala' => 'required|in:literal,numerica',
            'nivel' => ['nullable', 'string', Rule::in(['AD', 'A', 'B', 'C'])],
            'nota' => 'nullable|numeric|min:0|max:20',
            'conclusion_descriptiva' => 'nullable|string|max:1000',
        ], [
            'nivel.in' => 'El nivel de logro debe ser AD, A, B o C.',
        ]);

        $estudiante = Estudiante::with('seccion.grado')->findOrFail($validated['estudiante_id']);
        $competencia = Competencia::with('materia')->findOrFail($validated['competencia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);

        $autorizacion = $this->autorizarEscritura($request, $estudiante, $competencia, $periodo);
        if ($autorizacion !== null) {
            return $autorizacion;
        }

        $calificacion = $this->competencias->registrar(
            $estudiante,
            $competencia,
            $periodo,
            $validated,
            $request->user()
        );

        return response()->json([
            'message' => 'Calificación de competencia registrada correctamente',
            'calificacion' => $calificacion,
        ], 201);
    }

    /**
     * Registro masivo del aula: todas las competencias de un área en un periodo.
     */
    public function guardarLote(Request $request)
    {
        foreach ((array) $request->input('registros', []) as $indice => $registro) {
            if (isset($registro['nivel'])) {
                $request->merge([
                    "registros.{$indice}.nivel" => strtoupper(trim((string) $registro['nivel'])),
                ]);
            }
        }

        $validated = $request->validate([
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'escala' => 'required|in:literal,numerica',
            'registros' => 'required|array|min:1|max:200',
            'registros.*.estudiante_id' => 'required|exists:estudiantes,id',
            'registros.*.competencia_id' => 'required|exists:competencias,id',
            'registros.*.nivel' => ['nullable', 'string', Rule::in(['AD', 'A', 'B', 'C'])],
            'registros.*.nota' => 'nullable|numeric|min:0|max:20',
            'registros.*.conclusion_descriptiva' => 'nullable|string|max:1000',
        ], [
            'registros.*.nivel.in' => 'El nivel de logro debe ser AD, A, B o C.',
        ]);

        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);
        $guardados = 0;

        DB::transaction(function () use ($validated, $periodo, $request, &$guardados) {
            foreach ($validated['registros'] as $indice => $registro) {
                $estudiante = Estudiante::with('seccion.grado')->find($registro['estudiante_id']);
                $competencia = Competencia::with('materia')->find($registro['competencia_id']);

                if (! $estudiante || ! $competencia) {
                    throw ValidationException::withMessages([
                        "registros.{$indice}.estudiante_id" => 'El estudiante o la competencia ya no existe.',
                    ]);
                }

                $autorizacion = $this->verificarDocenteAsignado(
                    (int) $competencia->materia_id,
                    $estudiante->seccion_id,
                    (int) $periodo->id
                );

                if ($autorizacion !== true) {
                    throw ValidationException::withMessages([
                        "registros.{$indice}.competencia_id" => 'No tienes asignada esta área en esta sección y periodo.',
                    ]);
                }

                $this->competencias->registrar(
                    $estudiante,
                    $competencia,
                    $periodo,
                    [
                        'escala' => $validated['escala'],
                        'nivel' => $registro['nivel'] ?? null,
                        'nota' => $registro['nota'] ?? null,
                        'conclusion_descriptiva' => $registro['conclusion_descriptiva'] ?? null,
                    ],
                    $request->user()
                );

                $guardados++;
            }
        });

        return response()->json([
            'message' => "Se registraron {$guardados} calificaciones de competencia.",
            'guardados' => $guardados,
        ], 201);
    }

    /**
     * Libro de notas de un curso: estudiantes en filas y competencias en
     * columnas, con el nivel alcanzado en el periodo.
     */
    public function libroDeCurso(Request $request)
    {
        $validated = $request->validate([
            'seccion_id' => 'required|exists:secciones,id',
            'materia_id' => 'required|exists:materias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
        ]);

        $seccion = \App\Models\Seccion::with('grado:id,nombre,nivel,ciclo')->findOrFail($validated['seccion_id']);
        $materia = \App\Models\Materia::findOrFail($validated['materia_id']);
        $periodo = PeriodoAcademico::findOrFail($validated['periodo_academico_id']);

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $materia->id,
            (int) $seccion->id,
            (int) $periodo->id
        );
        if ($autorizacion !== true) {
            return $autorizacion;
        }

        $ciclo = $seccion->grado?->ciclo;

        $competencias = Competencia::query()
            ->activas()
            ->delCiclo($ciclo)
            ->where('materia_id', $materia->id)
            ->ordenadas()
            ->get(['id', 'codigo', 'nombre', 'orden']);

        $estudiantes = Estudiante::query()
            ->where('seccion_id', $seccion->id)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'dni']);

        $calificaciones = CalificacionCompetencia::query()
            ->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->whereIn('competencia_id', $competencias->pluck('id'))
            ->where('periodo_academico_id', $periodo->id)
            ->get()
            ->groupBy('estudiante_id');

        return response()->json([
            'seccion' => [
                'id' => $seccion->id,
                'nombre' => trim(($seccion->grado->nombre ?? '') . ' - ' . $seccion->nombre),
                'ciclo' => $ciclo,
            ],
            'materia' => ['id' => $materia->id, 'nombre' => $materia->nombre],
            'periodo' => ['id' => $periodo->id, 'nombre' => $periodo->nombre, 'anio' => $periodo->anio],
            'competencias' => $competencias,
            'estudiantes' => $estudiantes->map(function (Estudiante $estudiante) use ($calificaciones, $competencias) {
                $registros = ($calificaciones->get($estudiante->id) ?? collect())->keyBy('competencia_id');

                $niveles = $competencias->map(function (Competencia $competencia) use ($registros) {
                    $registro = $registros->get($competencia->id);

                    return [
                        'competencia_id' => $competencia->id,
                        'nivel' => $registro?->nivel,
                        'nota' => $registro?->nota !== null ? (float) $registro->nota : null,
                    ];
                })->values();

                return [
                    'id' => $estudiante->id,
                    'nombre_completo' => $estudiante->nombre_completo,
                    'apellido_paterno' => $estudiante->apellido_paterno,
                    'apellido_materno' => $estudiante->apellido_materno,
                    'nombres' => $estudiante->nombres,
                    'dni' => $estudiante->dni,
                    'niveles' => $niveles,
                    'registradas' => $niveles->filter(fn ($nivel) => $nivel['nivel'] !== null)->count(),
                ];
            })->values(),
        ]);
    }

    private function autorizarLectura(Request $request, Estudiante $estudiante)
    {
        $usuario = $request->user();

        if ($usuario->hasAnyRole(['admin', 'auxiliar'])) {
            return null;
        }

        if ($usuario->isEstudiante() && (int) $estudiante->user_id === (int) $usuario->id) {
            return null;
        }

        if ($usuario->isPadre()) {
            $esSuHijo = $estudiante->padres()->where('user_id', $usuario->id)->exists();

            return $esSuHijo ? null : response()->json(['message' => 'El estudiante no está a su cargo'], 403);
        }

        if ($usuario->isDocente()) {
            $tieneSeccion = $usuario->docente
                && \App\Models\AsignacionDocenteMateria::query()
                    ->where('docente_id', $usuario->docente->id)
                    ->where('seccion_id', $estudiante->seccion_id)
                    ->exists();

            if ($tieneSeccion) {
                return null;
            }

            return response()->json(['message' => 'No tiene acceso a este estudiante'], 403);
        }

        return response()->json(['message' => 'No tiene acceso a este estudiante'], 403);
    }

    private function normalizarNivel(Request $request): void
    {
        if ($request->filled('nivel')) {
            $request->merge(['nivel' => strtoupper(trim((string) $request->input('nivel')))]);
        }
    }

    private function autorizarEscritura(Request $request, Estudiante $estudiante, Competencia $competencia, PeriodoAcademico $periodo)
    {
        if ($estudiante->ciclo() !== $competencia->ciclo) {
            return response()->json([
                'message' => 'La competencia no corresponde al ciclo del estudiante.',
            ], 422);
        }

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $competencia->materia_id,
            $estudiante->seccion_id,
            (int) $periodo->id
        );

        return $autorizacion === true ? null : $autorizacion;
    }
}
