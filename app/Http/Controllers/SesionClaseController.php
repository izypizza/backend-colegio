<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDocenteMateria;
use App\Models\Estudiante;
use App\Models\SesionClase;
use App\Services\SesionClaseService;
use App\Traits\VerificaAutorizacionDocente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SesionClaseController extends Controller
{
    use VerificaAutorizacionDocente;

    public function __construct(private SesionClaseService $sesiones)
    {
    }

    /**
     * Sesiones de clase registradas, filtradas por asignación, sección o fecha.
     */
    public function index(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'nullable|exists:asignacion_docente_materia,id',
            'materia_id' => 'nullable|exists:materias,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $query = SesionClase::query()
            ->with([
                'asignacion:id,docente_id,materia_id,seccion_id,periodo_academico_id',
                'asignacion.materia:id,nombre',
                'asignacion.seccion:id,nombre,grado_id',
                'asignacion.seccion.grado:id,nombre',
                'asignacion.docente:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->withCount(['asistencias as registros'])
            ->entreFechas($request->input('desde'), $request->input('hasta'))
            ->when($request->filled('asignacion_id'), fn ($q) => $q->where('asignacion_docente_materia_id', $request->input('asignacion_id')))
            ->when($request->filled('materia_id'), fn ($q) => $q->whereHas('asignacion', fn ($a) => $a->where('materia_id', $request->input('materia_id'))))
            ->when($request->filled('seccion_id'), fn ($q) => $q->whereHas('asignacion', fn ($a) => $a->where('seccion_id', $request->input('seccion_id'))))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio');

        if ($request->user()->isDocente()) {
            $docenteId = $request->user()->docente?->id;
            $query->whereHas('asignacion', fn ($a) => $a->where('docente_id', $docenteId));
        }

        return response()->json($query->paginate($request->integer('per_page', 30)));
    }

    /**
     * Abre la sesión de una asignación en una fecha determinada.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asignacion_id' => 'required|exists:asignacion_docente_materia,id',
            'fecha' => 'required|date|before_or_equal:today',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio',
            'tema' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'fecha.before_or_equal' => 'No puedes abrir una sesión con fecha futura.',
        ]);

        $asignacion = AsignacionDocenteMateria::findOrFail($validated['asignacion_id']);
        $autorizacion = $this->autorizarAsignacion($request, $asignacion);
        if ($autorizacion !== null) {
            return $autorizacion;
        }

        $validacionCalendario = $this->validarCalendario($validated['fecha'], $asignacion);
        if ($validacionCalendario !== null) {
            return $validacionCalendario;
        }

        $sesion = $this->sesiones->abrirSesion(
            $asignacion,
            $validated['fecha'],
            $validated['hora_inicio'] ?? null,
            $validated['tema'] ?? null,
            $request->user()
        );

        if (! empty($validated['observaciones'])) {
            $sesion->update(['observaciones' => $validated['observaciones']]);
        }

        return response()->json([
            'message' => 'Sesión de clase disponible.',
            'sesion' => $this->cargar($sesion),
        ], 201);
    }

    public function show(string $id)
    {
        $sesion = SesionClase::findOrFail($id);

        return response()->json([
            'sesion' => $this->cargar($sesion),
            'resumen' => $this->sesiones->resumen($sesion),
        ]);
    }

    /**
     * Llamar lista: registra la asistencia de toda la sección en la sesión.
     */
    public function llamarLista(Request $request, string $id)
    {
        $sesion = SesionClase::with('asignacion')->findOrFail($id);

        $autorizacion = $this->autorizarAsignacion($request, $sesion->asignacion);
        if ($autorizacion !== null) {
            return $autorizacion;
        }

        if ($sesion->estado === 'anulada') {
            return response()->json(['message' => 'La sesión está anulada y no admite registros.'], 422);
        }

        $validated = $request->validate([
            'registros' => 'required|array|min:1',
            'registros.*.estudiante_id' => 'required|exists:estudiantes,id',
            'registros.*.estado' => ['required', Rule::in(['presente', 'tarde', 'ausente', 'justificado', 'permiso'])],
            'registros.*.minutos_tardanza' => 'nullable|integer|min:0|max:180',
            'registros.*.justificacion' => 'nullable|string|max:500',
        ], [
            'registros.*.estado.in' => 'El estado debe ser presente, tarde, ausente, justificado o permiso.',
        ]);

        $resultado = $this->sesiones->registrarLista($sesion, $validated['registros'], $request->user());

        return response()->json([
            'message' => 'Asistencia de la sesión registrada correctamente.',
            'resultado' => $resultado,
            'resumen' => $this->sesiones->resumen($sesion->fresh()),
        ], 201);
    }

    /**
     * Estudiantes de la sección con su estado en la sesión, listos para llamar lista.
     */
    public function hojaDeAsistencia(Request $request, string $id)
    {
        $sesion = SesionClase::with('asignacion')->findOrFail($id);
        $registros = $sesion->asistencias()->get()->keyBy('estudiante_id');

        $estudiantes = Estudiante::query()
            ->where('seccion_id', $sesion->asignacion->seccion_id)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'dni']);

        return response()->json([
            'sesion' => $this->cargar($sesion),
            'estudiantes' => $estudiantes->map(fn (Estudiante $estudiante) => [
                'id' => $estudiante->id,
                'nombres' => $estudiante->nombres,
                'apellido_paterno' => $estudiante->apellido_paterno,
                'apellido_materno' => $estudiante->apellido_materno,
                'dni' => $estudiante->dni,
                'estado' => $registros->get($estudiante->id)?->estado,
                'minutos_tardanza' => $registros->get($estudiante->id)?->minutos_tardanza,
                'justificacion' => $registros->get($estudiante->id)?->justificacion,
            ])->values(),
        ]);
    }

    public function anular(Request $request, string $id)
    {
        $sesion = SesionClase::with('asignacion')->findOrFail($id);
        $autorizacion = $this->autorizarAsignacion($request, $sesion->asignacion);
        if ($autorizacion !== null) {
            return $autorizacion;
        }

        $sesion->update(['estado' => 'anulada']);

        return response()->json([
            'message' => 'Sesión anulada. Sus registros no se contabilizan.',
            'sesion' => $this->cargar($sesion->fresh()),
        ]);
    }

    private function cargar(SesionClase $sesion): SesionClase
    {
        $sesion->load([
            'asignacion.materia:id,nombre',
            'asignacion.seccion:id,nombre,grado_id',
            'asignacion.seccion.grado:id,nombre',
            'asignacion.docente:id,nombres,apellido_paterno,apellido_materno',
        ]);
        $sesion->loadCount(['asistencias as registros']);

        return $sesion;
    }

    private function autorizarAsignacion(Request $request, ?AsignacionDocenteMateria $asignacion)
    {
        if (! $asignacion) {
            return response()->json(['message' => 'La sesión no tiene una asignación válida.'], 422);
        }

        if ($request->user()->hasAnyRole(['admin', 'auxiliar'])) {
            return null;
        }

        if (! $request->user()->isDocente()) {
            return response()->json(['message' => 'No tiene permisos para gestionar esta sesión.'], 403);
        }

        $autorizacion = $this->verificarDocenteAsignado(
            (int) $asignacion->materia_id,
            (int) $asignacion->seccion_id,
            (int) $asignacion->periodo_academico_id
        );

        return $autorizacion === true ? null : $autorizacion;
    }

    /**
     * Una sesión solo puede abrirse en un día en que ese curso tiene clase
     * según el horario, y siempre dentro de las fechas del periodo.
     */
    private function validarCalendario(string $fecha, AsignacionDocenteMateria $asignacion)
    {
        $dia = \Carbon\Carbon::parse($fecha)->startOfDay();
        $periodo = \App\Models\PeriodoAcademico::find($asignacion->periodo_academico_id);

        if ($periodo) {
            $inicio = $periodo->fecha_inicio ? \Carbon\Carbon::parse($periodo->fecha_inicio)->startOfDay() : null;
            $fin = $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin)->endOfDay() : null;

            if ($inicio && $dia->lt($inicio)) {
                return response()->json([
                    'message' => 'La fecha es anterior al inicio del periodo académico.',
                ], 422);
            }

            if ($fin && $dia->gt($fin)) {
                return response()->json([
                    'message' => 'La fecha es posterior al cierre del periodo académico.',
                ], 422);
            }
        }

        $nombresDia = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
            4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];
        $nombreDia = $nombresDia[$dia->dayOfWeek] ?? '';

        $diasConClase = \App\Models\Horario::query()
            ->where('seccion_id', $asignacion->seccion_id)
            ->where('materia_id', $asignacion->materia_id)
            ->pluck('dia')
            ->map(fn ($valor) => \App\Helpers\BusquedaHelper::normalizar($valor))
            ->unique();

        if ($diasConClase->isNotEmpty()) {
            if (! $diasConClase->contains($nombreDia)) {
                return response()->json([
                    'message' => 'Este curso no tiene clase programada el ' . ucfirst($nombreDia) . '. Revisa el horario de la sección.',
                ], 422);
            }

            return null;
        }

        if ($dia->isWeekend()) {
            return response()->json([
                'message' => 'No se registran clases en sábado ni domingo.',
            ], 422);
        }

        return null;
    }
}
