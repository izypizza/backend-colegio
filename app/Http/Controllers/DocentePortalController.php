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
     */
    public function misCalificaciones(Request $request)
    {
        $user = $request->user();

        if (! $user->docente) {
            return response()->json(['message' => 'Usuario no es docente'], 403);
        }

        $materiasIds = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->pluck('materia_id')
            ->unique();

        $calificaciones = Calificacion::whereIn('materia_id', $materiasIds)
            ->with(['estudiante.seccion.grado', 'materia', 'periodoAcademico'])
            ->get();

        return response()->json(['calificaciones' => $calificaciones]);
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

        $materiasIds = AsignacionDocenteMateria::where('docente_id', $user->docente->id)
            ->pluck('materia_id')
            ->unique();

        $query = Asistencia::whereIn('materia_id', $materiasIds)
            ->with(['estudiante.seccion', 'materia']);

        // Filtros opcionales
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        $asistencias = $query->orderBy('fecha', 'desc')->get();

        return response()->json(['asistencias' => $asistencias]);
    }
}