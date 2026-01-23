<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Horario::with(['seccion.grado', 'materia']);

        // Si es estudiante, solo mostrar horarios de su sección
        if ($user->role === 'estudiante' && $user->estudiante) {
            $query->where('seccion_id', $user->estudiante->seccion_id);
        }

        // Si es padre, mostrar horarios de las secciones de sus hijos
        if ($user->role === 'padre' && $user->padre) {
            $seccionesHijos = $user->padre->estudiantes()->pluck('seccion_id')->unique();
            $query->whereIn('seccion_id', $seccionesHijos);
        }

        // Si es docente, mostrar horarios de sus asignaciones
        if ($user->role === 'docente' && $user->docente) {
            $seccionesDocente = $user->docente->asignaciones()->pluck('seccion_id')->unique();
            $query->whereIn('seccion_id', $seccionesDocente);
        }

        // Filtros opcionales por parámetros
        if ($request->has('seccion_id')) {
            $query->where('seccion_id', $request->seccion_id);
        }

        if ($request->has('dia')) {
            $query->where('dia', $request->dia);
        }

        // Paginación
        if ($request->has('all') && $request->all === 'true') {
            $horarios = $query->orderBy('dia')->orderBy('hora_inicio')->get();
            return response()->json($horarios);
        }

        $perPage = $request->get('per_page', 50);
        $horarios = $query->orderBy('dia')->orderBy('hora_inicio')->paginate($perPage);
        
        return response()->json($horarios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seccion_id' => 'required|exists:secciones,id',
            'materia_id' => 'required|exists:materias,id',
            'dia' => 'required|string|max:255',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ]);

        $horario = Horario::create($validated);
        return response()->json($horario->load(['seccion', 'materia']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $horario = Horario::with(['seccion.grado', 'materia'])->findOrFail($id);
        return response()->json($horario);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $horario = Horario::findOrFail($id);
        
        $validated = $request->validate([
            'seccion_id' => 'sometimes|required|exists:secciones,id',
            'materia_id' => 'sometimes|required|exists:materias,id',
            'dia' => 'sometimes|required|string|max:255',
            'hora_inicio' => 'sometimes|required|date_format:H:i',
            'hora_fin' => 'sometimes|required|date_format:H:i|after:hora_inicio'
        ]);

        $horario->update($validated);
        return response()->json($horario->load(['seccion', 'materia']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
