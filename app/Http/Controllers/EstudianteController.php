<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use App\Helpers\AcademicYearHelper;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Estudiante::with(['seccion.grado', 'padres']);
        
        // Paginación para mejorar performance (328+ estudiantes)
        if ($request->has('all') && $request->all === 'true') {
            $estudiantes = $query->get();
            return response()->json($estudiantes);
        }

        $perPage = $request->get('per_page', 100);
        $estudiantes = $query->paginate($perPage);
        return response()->json($estudiantes);
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
                'dni' => 'required|string|size:8|unique:estudiantes,dni|regex:/^[0-9]{8}$/',
                'fecha_nacimiento' => 'required|date|before:today|after:' . now()->subYears(25)->format('Y-m-d'),
                'seccion_id' => 'required|exists:secciones,id',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500'
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
                'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
                'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
                'fecha_nacimiento.after' => 'El estudiante debe tener menos de 25 años. Verifica la fecha de nacimiento',
                'seccion_id.required' => 'Debe seleccionar una sección',
                'seccion_id.exists' => 'La sección seleccionada no existe',
                'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9',
                'direccion.min' => 'La dirección debe tener al menos 5 caracteres'
            ]);

            // Validación adicional de edad según grado y período académico
            $seccion = \App\Models\Seccion::with('grado')->findOrFail($validated['seccion_id']);
            $nombreGrado = $seccion->grado->nombre;
            
            // Validar edad usando el helper centralizado
            $validacion = AcademicYearHelper::validarEdadParaGrado(
                $validated['fecha_nacimiento'],
                $nombreGrado
            );
            
            if (!$validacion['valido']) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['fecha_nacimiento' => [$validacion['mensaje']]]
                ], 422);
            }

            // Verificar capacidad de la sección
            $seccion = \App\Models\Seccion::findOrFail($validated['seccion_id']);
            $cantidadActual = \App\Models\Estudiante::where('seccion_id', $seccion->id)->count();
            
            if ($seccion->capacidad && $cantidadActual >= $seccion->capacidad) {
                return response()->json([
                    'message' => 'La sección ha alcanzado su capacidad máxima',
                    'errors' => ['seccion_id' => ['La sección está llena']]
                ], 422);
            }

            $estudiante = Estudiante::create($validated);
            return response()->json([
                'message' => 'Estudiante creado correctamente',
                'estudiante' => $estudiante->load(['seccion.grado'])
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $estudiante = Estudiante::with(['seccion.grado', 'padres', 'asistencias', 'calificaciones'])->findOrFail($id);
        return response()->json($estudiante);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);
            
            $validated = $request->validate([
                'nombres' => 'sometimes|required|string|min:2|max:255',
                'apellido_paterno' => 'sometimes|required|string|min:2|max:255',
                'apellido_materno' => 'sometimes|required|string|min:2|max:255',
                'dni' => 'sometimes|required|string|size:8|unique:estudiantes,dni,' . $id . '|regex:/^[0-9]{8}$/',
                'fecha_nacimiento' => 'sometimes|required|date|before:today|after:' . now()->subYears(25)->format('Y-m-d'),
                'seccion_id' => 'sometimes|required|exists:secciones,id',
                'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
                'direccion' => 'nullable|string|min:5|max:500'
            ], [
                'nombres.min' => 'Los nombres deben tener al menos 2 caracteres',
                'apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres',
                'apellido_materno.min' => 'El apellido materno debe tener al menos 2 caracteres',
                'dni.unique' => 'El DNI ya está registrado en el sistema',
                'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
                'dni.regex' => 'El DNI solo debe contener números',
                'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9'
            ]);

            // Si cambia de sección, verificar capacidad
            if (isset($validated['seccion_id']) && $validated['seccion_id'] != $estudiante->seccion_id) {
                $seccion = \App\Models\Seccion::findOrFail($validated['seccion_id']);
                $cantidadActual = \App\Models\Estudiante::where('seccion_id', $seccion->id)->count();
                
                if ($seccion->capacidad && $cantidadActual >= $seccion->capacidad) {
                    return response()->json([
                        'message' => 'La sección ha alcanzado su capacidad máxima',
                        'errors' => ['seccion_id' => ['La sección está llena']]
                    ], 422);
                }
                
                // Validar edad según el nuevo grado y período académico usando el helper
                $fechaNacimientoStr = isset($validated['fecha_nacimiento']) 
                    ? $validated['fecha_nacimiento'] 
                    : $estudiante->fecha_nacimiento;
                
                $seccionNueva = \App\Models\Seccion::with('grado')->findOrFail($validated['seccion_id']);
                $nombreGrado = $seccionNueva->grado->nombre;
                
                $validacion = AcademicYearHelper::validarEdadParaGrado($fechaNacimientoStr, $nombreGrado);
                
                if (!$validacion['valido']) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors' => ['seccion_id' => [$validacion['mensaje']]]
                    ], 422);
                }
            }

            $estudiante->update($validated);
            return response()->json([
                'message' => 'Estudiante actualizado correctamente',
                'estudiante' => $estudiante->load(['seccion.grado'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Restricciones: no se permite eliminar si el estudiante tiene registros académicos
     * o préstamos activos —el historial escolar es irrenunciable.
     */
    public function destroy(string $id)
    {
        try {
            $estudiante = Estudiante::withCount([
                'calificaciones',
                'asistencias',
            ])->findOrFail($id);

            $nombre = $estudiante->nombre_completo;

            // Bloquear si tiene calificaciones registradas
            if ($estudiante->calificaciones_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar a {$nombre} porque tiene {$estudiante->calificaciones_count} calificación(es) registrada(s). El historial académico no puede borrarse; usa la opción de cambiar estado a 'egresado'.",
                    'calificaciones' => $estudiante->calificaciones_count,
                ], 422);
            }

            // Bloquear si tiene asistencias registradas
            if ($estudiante->asistencias_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar a {$nombre} porque tiene {$estudiante->asistencias_count} registro(s) de asistencia. El historial académico no puede borrarse; usa la opción de cambiar estado a 'egresado'.",
                    'asistencias' => $estudiante->asistencias_count,
                ], 422);
            }

            // Bloquear si tiene préstamos pendientes o activos
            $prestamosActivos = \App\Models\PrestamoLibro::where('estudiante_id', $estudiante->id)
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->where('devuelto', false)
                ->count();

            if ($prestamosActivos > 0) {
                return response()->json([
                    'message' => "No se puede eliminar a {$nombre} porque tiene {$prestamosActivos} préstamo(s) de biblioteca sin devolver.",
                    'prestamos_activos' => $prestamosActivos,
                ], 422);
            }

            // Desvincular usuario si lo tiene asignado
            if ($estudiante->user_id) {
                \App\Models\User::where('id', $estudiante->user_id)->delete();
            }

            $estudiante->delete();

            return response()->json([
                'message' => "Estudiante {$nombre} eliminado correctamente",
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el estudiante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
