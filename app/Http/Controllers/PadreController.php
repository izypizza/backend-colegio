<?php

namespace App\Http\Controllers;

use App\Models\Padre;
use Illuminate\Http\Request;

class PadreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Padre::with(['estudiantes']);

        // Paginación para mejorar performance
        if ($request->has('all') && $request->all === 'true') {
            $padres = $query->get();

            return response()->json($padres);
        }

        $perPage = $request->get('per_page', 50);
        $padres = $query->paginate($perPage);

        // Retornar en formato consistente
        return response()->json([
            'data' => $padres->items(),
            'current_page' => $padres->currentPage(),
            'last_page' => $padres->lastPage(),
            'per_page' => $padres->perPage(),
            'total' => $padres->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombres' => 'required|string|min:2|max:255',
                'apellido_paterno' => 'required|string|min:2|max:255',
                'apellido_materno' => 'required|string|min:2|max:255',
                'dni' => 'required|string|size:8|unique:padres,dni|regex:/^[0-9]{8}$/',
                'email' => 'nullable|email|unique:padres,email|max:255',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500',
                'ocupacion' => 'nullable|string|min:3|max:255',
            ], [
                'nombres.required' => 'Los nombres son obligatorios',
                'nombres.min' => 'Los nombres deben tener al menos 2 caracteres',
                'apellido_paterno.required' => 'El apellido paterno es obligatorio',
                'apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres',
                'apellido_materno.required' => 'El apellido materno es obligatorio',
                'apellido_materno.min' => 'El apellido materno debe tener al menos 2 caracteres',
                'dni.required' => 'El DNI es obligatorio',
                'dni.unique' => 'El DNI ya está registrado en el sistema',
                'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
                'dni.regex' => 'El DNI solo debe contener números',
                'email.unique' => 'El email ya está registrado en el sistema',
                'email.email' => 'El formato del email no es válido',
                'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9',
                'direccion.min' => 'La dirección debe tener al menos 5 caracteres',
                'ocupacion.min' => 'La ocupación debe tener al menos 3 caracteres',
            ]);

            $padre = Padre::create($validated);

            return response()->json([
                'message' => 'Padre creado correctamente',
                'padre' => $padre,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $padre = Padre::with(['estudiantes'])->findOrFail($id);

        return response()->json($padre);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $padre = Padre::findOrFail($id);

            $validated = $request->validate([
                'nombres' => 'sometimes|required|string|min:2|max:255',
                'apellido_paterno' => 'sometimes|required|string|min:2|max:255',
                'apellido_materno' => 'sometimes|required|string|min:2|max:255',
                'dni' => 'sometimes|required|string|size:8|unique:padres,dni,'.$id.'|regex:/^[0-9]{8}$/',
                'email' => 'nullable|email|unique:padres,email,'.$id.'|max:255',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500',
                'ocupacion' => 'nullable|string|min:3|max:255',
            ], [
                'nombres.min' => 'Los nombres deben tener al menos 2 caracteres',
                'apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres',
                'apellido_materno.min' => 'El apellido materno debe tener al menos 2 caracteres',
                'dni.unique' => 'El DNI ya está registrado en el sistema',
                'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
                'dni.regex' => 'El DNI solo debe contener números',
                'email.unique' => 'El email ya está registrado en el sistema',
                'email.email' => 'El formato del email no es válido',
                'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9',
                'direccion.min' => 'La dirección debe tener al menos 5 caracteres',
                'ocupacion.min' => 'La ocupación debe tener al menos 3 caracteres',
            ]);

            $padre->update($validated);

            return response()->json([
                'message' => 'Padre actualizado correctamente',
                'padre' => $padre,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Restricciones: un padre con hijos matriculados activamente vinculados
     * no puede eliminarse —es el tutor legal registrado.
     */
    public function destroy(string $id)
    {
        try {
            $padre = Padre::withCount('estudiantes')->findOrFail($id);
            $nombre = $padre->nombre_completo;

            // Bloquear si tiene hijos vinculados
            if ($padre->estudiantes_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar a {$nombre} porque tiene {$padre->estudiantes_count} estudiante(s) vinculado(s). Desvincula primero a los estudiantes o usa la opción de desactivar cuenta.",
                    'estudiantes_vinculados' => $padre->estudiantes_count,
                ], 422);
            }

            // Desvincular usuario si lo tiene asignado
            if ($padre->user_id) {
                \App\Models\User::where('id', $padre->user_id)->delete();
            }

            $padre->delete();

            return response()->json([
                'message' => "Padre {$nombre} eliminado correctamente",
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Padre no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asociar un estudiante a un padre
     */
    public function asociarEstudiante(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'estudiante_id' => 'required|exists:estudiantes,id',
            ]);

            $padre = Padre::findOrFail($id);
            
            // Verificar si ya está asociado
            if ($padre->estudiantes()->where('estudiante_id', $validated['estudiante_id'])->exists()) {
                return response()->json([
                    'message' => 'El estudiante ya está vinculado a este padre',
                ], 422);
            }

            $padre->estudiantes()->attach($validated['estudiante_id']);

            return response()->json([
                'message' => 'Estudiante asociado correctamente',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asociar el estudiante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desasociar un estudiante de un padre
     */
    public function desasociarEstudiante(string $padreId, string $estudianteId)
    {
        try {
            $padre = Padre::findOrFail($padreId);
            
            // Verificar si está asociado
            if (!$padre->estudiantes()->where('estudiante_id', $estudianteId)->exists()) {
                return response()->json([
                    'message' => 'El estudiante no está vinculado a este padre',
                ], 422);
            }

            $padre->estudiantes()->detach($estudianteId);

            return response()->json([
                'message' => 'Estudiante desvinculado correctamente',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al desvincular el estudiante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lista de estudiantes disponibles para asociar
     */
    public function estudiantesDisponibles(string $id)
    {
        try {
            $padre = Padre::findOrFail($id);
            
            // Obtener IDs de estudiantes ya asociados
            $estudiantesAsociados = $padre->estudiantes()->pluck('estudiante_id');
            
            // Obtener estudiantes NO asociados
            $disponibles = \App\Models\Estudiante::with(['seccion.grado'])
                ->whereNotIn('id', $estudiantesAsociados)
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombres')
                ->get();

            return response()->json($disponibles);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estudiantes disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
