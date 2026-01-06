<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;

class DocenteController extends Controller
{
    const ESPECIALIDADES = [
        'Matemáticas',
        'Comunicación',
        'Ciencias Sociales',
        'Ciencia y Tecnología',
        'Educación Física',
        'Arte y Cultura',
        'Inglés',
        'Educación Religiosa',
        'Tutoría',
        'Educación para el Trabajo',
        'Desarrollo Personal, Ciudadanía y Cívica'
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Docente::with(['asignaciones']);
        
        // Paginación para mejorar performance
        if ($request->has('all') && $request->all === 'true') {
            $docentes = $query->get();
            return response()->json($docentes);
        }

        $perPage = $request->get('per_page', 50);
        $docentes = $query->paginate($perPage);
        
        // Retornar en formato consistente
        return response()->json([
            'data' => $docentes->items(),
            'current_page' => $docentes->currentPage(),
            'last_page' => $docentes->lastPage(),
            'per_page' => $docentes->perPage(),
            'total' => $docentes->total(),
            'especialidades' => self::ESPECIALIDADES
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
                'dni' => 'required|string|size:8|unique:docentes,dni|regex:/^[0-9]{8}$/',
                'email' => 'required|email|unique:docentes,email|max:255',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500',
                'especialidad' => 'required|string|in:' . implode(',', self::ESPECIALIDADES)
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
                'email.required' => 'El email es obligatorio',
                'email.unique' => 'El email ya está registrado en el sistema',
                'email.email' => 'El formato del email no es válido',
                'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9',
                'direccion.min' => 'La dirección debe tener al menos 5 caracteres',
                'especialidad.required' => 'La especialidad es obligatoria',
                'especialidad.in' => 'La especialidad seleccionada no es válida'
            ]);

            $docente = Docente::create($validated);
            return response()->json([
                'message' => 'Docente creado correctamente',
                'docente' => $docente
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $docente = Docente::with(['asignaciones.materia', 'asignaciones.seccion'])->findOrFail($id);
        return response()->json($docente);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $docente = Docente::findOrFail($id);
            
            $validated = $request->validate([
                'nombres' => 'sometimes|required|string|min:2|max:255',
                'apellido_paterno' => 'sometimes|required|string|min:2|max:255',
                'apellido_materno' => 'sometimes|required|string|min:2|max:255',
                'dni' => 'sometimes|required|string|size:8|unique:docentes,dni,' . $id . '|regex:/^[0-9]{8}$/',
                'email' => 'sometimes|required|email|unique:docentes,email,' . $id . '|max:255',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500',
                'especialidad' => 'sometimes|required|string|in:' . implode(',', self::ESPECIALIDADES)
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
                'especialidad.in' => 'La especialidad seleccionada no es válida'
            ]);

            $docente->update($validated);
            return response()->json([
                'message' => 'Docente actualizado correctamente',
                'docente' => $docente
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $docente = Docente::findOrFail($id);
            $nombre = $docente->nombre_completo;
            $docente->delete();
            
            return response()->json([
                'message' => "Docente {$nombre} eliminado correctamente"
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
