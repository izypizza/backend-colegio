<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDocenteMateria;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class DocentePortalController extends Controller
{
    /**
     * Ver mis asignaciones (materias que enseño)
     */
    public function misAsignaciones(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        $asignaciones = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->with(['materia', 'seccion.grado', 'periodoAcademico'])
            ->get();

        return response()->json(['asignaciones' => $asignaciones]);
    }

    /**
     * Ver estudiantes de mis secciones
     */
    public function misEstudiantes(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        $seccionesIds = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->pluck('seccion_id')
            ->unique();

        $estudiantes = Estudiante::whereIn('seccion_id', $seccionesIds)
            ->with(['seccion.grado'])
            ->get();

        return response()->json(['estudiantes' => $estudiantes]);
    }

    /**
     * Registrar asistencia de estudiantes
     */
    public function registrarAsistencia(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id' => 'required|exists:materias,id',
            'fecha' => 'required|date',
            'estado' => 'required|in:presente,tarde,ausente',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $user = $request->user();

        // Verificar que el docente enseña esta materia
        $estudiante = Estudiante::findOrFail($validated['estudiante_id']);
        $asignacion = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->where('materia_id', $validated['materia_id'])
            ->where('seccion_id', $estudiante->seccion_id)
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'No tiene permiso para registrar asistencia en esta materia o sección'], 403);
        }

        $asistencia = Asistencia::updateOrCreate(
            [
                'estudiante_id' => $validated['estudiante_id'],
                'materia_id' => $validated['materia_id'],
                'fecha' => $validated['fecha'],
            ],
            [
                'estado' => $validated['estado'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]
        );

        return response()->json($asistencia->load(['estudiante', 'materia']), 201);
    }

    /**
     * Registrar/Actualizar calificaciones
     */
    public function registrarCalificacion(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id' => 'required|exists:materias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'nota' => 'required|numeric|min:0|max:20',
        ]);

        $user = $request->user();

        // Verificar que el docente enseña esta materia
        $asignacion = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->where('materia_id', $validated['materia_id'])
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'No tiene permiso para esta materia'], 403);
        }

        $calificacion = Calificacion::updateOrCreate(
            [
                'estudiante_id' => $validated['estudiante_id'],
                'materia_id' => $validated['materia_id'],
                'periodo_academico_id' => $validated['periodo_academico_id'],
            ],
            [
                'nota' => $validated['nota'],
            ]
        );

        return response()->json($calificacion->load(['estudiante', 'materia', 'periodoAcademico']), 201);
    }

    /**
     * Ver calificaciones de mis estudiantes
     * Optimizado con paginación y filtros avanzados
     */
    public function misCalificaciones(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        // Validar filtros
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'materia_id' => 'nullable|exists:materias,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'nota_minima' => 'nullable|numeric|min:0|max:20',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        // Obtener periodo activo
        $periodoActual = \App\Models\PeriodoAcademico::where('estado', 'activo')->first();
        
        // Obtener materias y secciones del docente
        $asignaciones = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->when($periodoActual && !$request->filled('periodo_academico_id'), function($q) use ($periodoActual) {
                return $q->where('periodo_academico_id', $periodoActual->id);
            })
            ->get(['materia_id', 'seccion_id']);

        $materiasIds = $asignaciones->pluck('materia_id')->unique();
        $seccionesIds = $asignaciones->pluck('seccion_id')->unique();

        // Query optimizado
        $query = Calificacion::select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'observaciones', 'created_at')
            ->whereIn('materia_id', $materiasIds)
            ->whereHas('estudiante', function($q) use ($seccionesIds) {
                $q->whereIn('seccion_id', $seccionesIds);
            })
            ->with([
                'estudiante:id,nombres,apellido_paterno,apellido_materno,seccion_id',
                'estudiante.seccion:id,nombre,grado_id',
                'estudiante.seccion.grado:id,nombre',
                'materia:id,nombre',
                'periodoAcademico:id,nombre,estado'
            ]);
            
        // Filtro por periodo (por defecto el activo)
        if ($request->filled('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        } elseif ($periodoActual) {
            $query->where('periodo_academico_id', $periodoActual->id);
        }
        
        // Filtro por materia específica
        if ($request->filled('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        // Filtro por sección específica
        if ($request->filled('seccion_id')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('seccion_id', $request->seccion_id);
            });
        }

        // Filtro por nota mínima
        if ($request->filled('nota_minima')) {
            $query->where('nota', '>=', $request->nota_minima);
        }

        // Orden por defecto
        $query->orderBy('created_at', 'desc');

        // Paginación (por defecto 50 registros)
        $perPage = $request->get('per_page', 50);
        $calificaciones = $query->paginate($perPage);

        return response()->json($calificaciones);
    }

    /**
     * Ver asistencias de mis estudiantes
     */
    public function misAsistencias(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        // Obtener solo las materias del periodo actual
        $periodoActual = \App\Models\PeriodoAcademico::where('estado', 'activo')->first();
        
        $materiasIds = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->when($periodoActual, function($q) use ($periodoActual) {
                return $q->where('periodo_academico_id', $periodoActual->id);
            })
            ->pluck('materia_id')
            ->unique();

        $query = Asistencia::whereIn('materia_id', $materiasIds)
            ->with(['estudiante:id,nombres,apellido_paterno,apellido_materno,seccion_id', 'estudiante.seccion:id,nombre', 'materia:id,nombre'])
            ->select('id', 'estudiante_id', 'materia_id', 'fecha', 'estado', 'observaciones');

        // Filtros opcionales
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        } else {
            // Por defecto, solo últimos 30 días
            $query->where('fecha', '>=', now()->subDays(30));
        }

        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        $asistencias = $query->orderBy('fecha', 'desc')->limit(1000)->get();

        return response()->json(['asistencias' => $asistencias]);
    }

    /**
     * Verificar si el docente es tutor de alguna sección
     */
    public function esTutor(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        $asignacionTutor = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->where('es_tutor', true)
            ->where(function($query) {
                $query->whereNull('tutor_hasta')
                      ->orWhere('tutor_hasta', '>=', now());
            })
            ->with(['seccion.grado', 'periodoAcademico'])
            ->first();

        if (!$asignacionTutor) {
            return response()->json(['es_tutor' => false]);
        }

        return response()->json([
            'es_tutor' => true,
            'seccion' => $asignacionTutor->seccion,
            'periodo' => $asignacionTutor->periodoAcademico,
            'tutor_hasta' => $asignacionTutor->tutor_hasta
        ]);
    }

    /**
     * Ver calificaciones de la sección como tutor
     */
    public function tutorCalificaciones(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        // Verificar que es tutor activo
        $asignacionTutor = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->where('es_tutor', true)
            ->where(function($query) {
                $query->whereNull('tutor_hasta')
                      ->orWhere('tutor_hasta', '>=', now());
            })
            ->first();

        if (!$asignacionTutor) {
            return response()->json(['message' => 'No es tutor activo de ninguna sección'], 403);
        }

        // Obtener estudiantes de la sección
        $estudiantesIds = Estudiante::where('seccion_id', $asignacionTutor->seccion_id)
            ->pluck('id');

        $query = Calificacion::whereIn('estudiante_id', $estudiantesIds)
            ->with([
                'estudiante:id,nombres,apellido_paterno,apellido_materno,seccion_id', 
                'materia:id,nombre', 
                'periodoAcademico:id,nombre'
            ])
            ->select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'modificaciones_count', 'ultima_modificacion');

        // Filtro por periodo
        if ($request->has('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        } else {
            // Por defecto, periodo actual del tutor
            $query->where('periodo_academico_id', $asignacionTutor->periodo_academico_id);
        }

        // Filtro por estudiante
        if ($request->has('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        $calificaciones = $query->orderBy('estudiante_id')
            ->orderBy('materia_id')
            ->limit(1000)
            ->get();

        return response()->json([
            'calificaciones' => $calificaciones,
            'seccion' => $asignacionTutor->seccion
        ]);
    }

    /**
     * Ver asistencias de la sección como tutor
     */
    public function tutorAsistencias(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        // Verificar que es tutor activo
        $asignacionTutor = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->where('es_tutor', true)
            ->where(function($query) {
                $query->whereNull('tutor_hasta')
                      ->orWhere('tutor_hasta', '>=', now());
            })
            ->first();

        if (!$asignacionTutor) {
            return response()->json(['message' => 'No es tutor activo de ninguna sección'], 403);
        }

        // Obtener estudiantes de la sección
        $estudiantesIds = Estudiante::where('seccion_id', $asignacionTutor->seccion_id)
            ->pluck('id');

        $query = Asistencia::whereIn('estudiante_id', $estudiantesIds)
            ->with([
                'estudiante:id,nombres,apellido_paterno,apellido_materno', 
                'materia:id,nombre'
            ])
            ->select('id', 'estudiante_id', 'materia_id', 'fecha', 'estado', 'observaciones');

        // Filtros de fecha
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        } else {
            // Por defecto, últimos 30 días
            $query->where('fecha', '>=', now()->subDays(30));
        }

        // Filtro por estudiante
        if ($request->has('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        // Filtro por materia
        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        $asistencias = $query->orderBy('fecha', 'desc')
            ->limit(1000)
            ->get();

        return response()->json([
            'asistencias' => $asistencias,
            'seccion' => $asignacionTutor->seccion
        ]);
    }
}