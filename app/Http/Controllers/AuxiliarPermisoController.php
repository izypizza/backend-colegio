<?php

namespace App\Http\Controllers;

use App\Models\AuxiliarPermisoEspecial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuxiliarPermisoController extends Controller
{
    // Listar usuarios auxiliares
    public function getAuxiliares()
    {
        try {
            $auxiliares = User::where('role', 'auxiliar')
                ->where('is_active', true)
                ->select('id', 'name', 'email', 'role')
                ->get();
            
            return response()->json(['auxiliares' => $auxiliares]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los auxiliares'], 500);
        }
    }

    // Listar todos los permisos especiales
    public function index()
    {
        try {
            $permisos = AuxiliarPermisoEspecial::with('user')->get();
            return response()->json($permisos);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los permisos especiales'], 500);
        }
    }

    // Obtener permisos de un auxiliar específico
    public function show($userId)
    {
        try {
            $permiso = AuxiliarPermisoEspecial::where('user_id', $userId)->first();
            
            if (!$permiso) {
                return response()->json([
                    'user_id' => $userId,
                    'puede_editar_estudiantes' => false,
                    'puede_editar_asistencias' => false,
                    'puede_editar_calificaciones' => false,
                    'esta_activo' => false
                ]);
            }

            return response()->json([
                ...$permiso->toArray(),
                'esta_activo' => $permiso->estaActivo()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los permisos'], 500);
        }
    }

    // Crear o actualizar permisos especiales
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'puede_editar_estudiantes' => 'boolean',
                'puede_editar_asistencias' => 'boolean',
                'puede_editar_calificaciones' => 'boolean',
                'activado_hasta' => 'nullable|date|after:now',
                'motivo' => 'required|string|max:500'
            ], [
                'user_id.required' => 'El usuario es requerido',
                'user_id.exists' => 'El usuario no existe',
                'activado_hasta.after' => 'La fecha de expiración debe ser futura',
                'motivo.required' => 'El motivo es requerido',
                'motivo.max' => 'El motivo no debe exceder 500 caracteres'
            ]);

            // Verificar que el usuario sea auxiliar
            $user = User::findOrFail($validated['user_id']);
            if ($user->role !== 'auxiliar') {
                return response()->json(['message' => 'El usuario no es un auxiliar'], 422);
            }

            // Registrar quien activa el permiso
            $validated['activado_por'] = Auth::id();

            $permiso = AuxiliarPermisoEspecial::updateOrCreate(
                ['user_id' => $validated['user_id']],
                $validated
            );

            return response()->json([
                'message' => 'Permisos especiales configurados exitosamente',
                'permiso' => $permiso
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al configurar los permisos: ' . $e->getMessage()], 500);
        }
    }

    // Desactivar todos los permisos de un auxiliar
    public function destroy($userId)
    {
        try {
            $permiso = AuxiliarPermisoEspecial::where('user_id', $userId)->first();
            
            if ($permiso) {
                $permiso->puede_editar_estudiantes = false;
                $permiso->puede_editar_asistencias = false;
                $permiso->puede_editar_calificaciones = false;
                $permiso->activado_hasta = now(); // Expira inmediatamente
                $permiso->save();

                return response()->json(['message' => 'Permisos especiales desactivados exitosamente']);
            }

            return response()->json(['message' => 'No se encontraron permisos para este auxiliar']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al desactivar los permisos'], 500);
        }
    }

    // Verificar permisos del usuario autenticado
    public function miPermiso()
    {
        try {
            $userId = Auth::id();
            $permiso = AuxiliarPermisoEspecial::where('user_id', $userId)->first();

            if (!$permiso || !$permiso->estaActivo()) {
                return response()->json([
                    'puede_editar_estudiantes' => false,
                    'puede_editar_asistencias' => false,
                    'puede_editar_calificaciones' => false
                ]);
            }

            return response()->json([
                'puede_editar_estudiantes' => $permiso->puede_editar_estudiantes,
                'puede_editar_asistencias' => $permiso->puede_editar_asistencias,
                'puede_editar_calificaciones' => $permiso->puede_editar_calificaciones,
                'activado_hasta' => $permiso->activado_hasta
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener mis permisos'], 500);
        }
    }
}
