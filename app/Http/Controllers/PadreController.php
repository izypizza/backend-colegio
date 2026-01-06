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
     */
    public function destroy(string $id)
    {
        try {
            $padre = Padre::findOrFail($id);
            $nombre = $padre->nombre_completo;
            $padre->delete();

            return response()->json([
                'message' => "Padre {$nombre} eliminado correctamente",
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
