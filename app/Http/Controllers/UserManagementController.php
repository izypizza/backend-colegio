<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Padre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Listar todos los usuarios con sus roles asociados
     */
    public function index()
    {
        $users = User::with(['estudiante.seccion.grado', 'docente', 'padre'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'persona' => $this->getPersonaInfo($user),
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Obtener información de la persona asociada al usuario
     */
    private function getPersonaInfo($user)
    {
        switch ($user->role) {
            case 'estudiante':
                if ($user->estudiante) {
                    return [
                        'id' => $user->estudiante->id,
                        'nombre_completo' => "{$user->estudiante->nombres} {$user->estudiante->apellido_paterno} {$user->estudiante->apellido_materno}",
                        'dni' => $user->estudiante->dni,
                        'estado' => $user->estudiante->estado,
                        'seccion' => $user->estudiante->seccion->nombre ?? null,
                        'grado' => $user->estudiante->seccion->grado->nombre ?? null,
                    ];
                }
                break;
            
            case 'docente':
                if ($user->docente) {
                    return [
                        'id' => $user->docente->id,
                        'nombre_completo' => $user->docente->nombre_completo,
                        'dni' => $user->docente->dni,
                        'especialidad' => $user->docente->especialidad,
                    ];
                }
                break;
            
            case 'padre':
                if ($user->padre) {
                    return [
                        'id' => $user->padre->id,
                        'nombre_completo' => $user->padre->nombre_completo,
                        'dni' => $user->padre->dni,
                        'hijos_count' => $user->padre->estudiantes->count(),
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Crear usuario para una persona existente
     */
    public function store(Request $request)
    {
        // Si viene persona_tipo asumimos flujo vinculado a persona
        if ($request->filled('persona_tipo')) {
            $validated = $request->validate([
                'persona_id' => 'required|integer',
                'persona_tipo' => 'required|in:estudiante,docente,padre,auxiliar',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'is_active' => 'boolean',
            ]);

            // Verificar que la persona existe y no tiene usuario
            $persona = $this->getPersona($validated['persona_tipo'], $validated['persona_id']);
            
            if (!$persona) {
                return response()->json([
                    'message' => 'La persona no existe'
                ], 404);
            }

            if ($persona->user_id) {
                return response()->json([
                    'message' => 'Esta persona ya tiene un usuario asignado'
                ], 422);
            }

            // Crear el usuario
            $user = User::create([
                'name' => $this->getNombreCompleto($persona, $validated['persona_tipo']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['persona_tipo'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Vincular usuario con la persona
            $persona->update(['user_id' => $user->id]);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'user' => $user->load(['estudiante', 'docente', 'padre'])
            ], 201);
        }

        // Flujo para usuarios sin persona (auxiliar / bibliotecario)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:auxiliar,bibliotecario',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
        ], 201);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:6',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user
        ]);
    }

    /**
     * Activar/Desactivar usuario
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Validar que no se puede activar estudiantes egresados o suspendidos
        if ($user->role === 'estudiante' && $user->estudiante) {
            if ($user->estudiante->estado === 'egresado') {
                return response()->json([
                    'message' => 'No se puede activar un estudiante egresado. Su estado debe ser "activo" primero.'
                ], 422);
            }
            
            if ($user->estudiante->estado === 'suspendido' && !$user->is_active) {
                return response()->json([
                    'message' => 'No se puede activar un estudiante suspendido. Cambie su estado a "activo" primero.'
                ], 422);
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
            'user' => $user
        ]);
    }

    /**
     * Obtener personas sin usuario asignado
     */
    public function personasSinUsuario($tipo)
    {
        if (!in_array($tipo, ['estudiante', 'docente', 'padre'])) {
            return response()->json(['message' => 'Tipo inválido'], 400);
        }

        $personas = [];

        switch ($tipo) {
            case 'estudiante':
                $personas = Estudiante::whereNull('user_id')
                    ->where('estado', 'activo')
                    ->with('seccion.grado')
                    ->get()
                    ->map(function($e) {
                        return [
                            'id' => $e->id,
                            'nombre_completo' => "{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}",
                            'dni' => $e->dni,
                            'email' => $e->email,
                            'seccion' => $e->seccion->nombre ?? null,
                            'grado' => $e->seccion->grado->nombre ?? null,
                        ];
                    });
                break;
            
            case 'docente':
                $personas = Docente::whereNull('user_id')
                    ->get()
                    ->map(function($d) {
                        return [
                            'id' => $d->id,
                            'nombre_completo' => $d->nombre_completo,
                            'dni' => $d->dni,
                            'email' => $d->email,
                            'especialidad' => $d->especialidad,
                        ];
                    });
                break;
            
            case 'padre':
                $personas = Padre::whereNull('user_id')
                    ->withCount('estudiantes')
                    ->get()
                    ->map(function($p) {
                        return [
                            'id' => $p->id,
                            'nombre_completo' => $p->nombre_completo,
                            'dni' => $p->dni,
                            'email' => $p->email,
                            'hijos_count' => $p->estudiantes_count,
                        ];
                    });
                break;
        }

        return response()->json(['personas' => $personas]);
    }

    /**
     * Actualizar estado de estudiante
     */
    public function updateEstadoEstudiante(Request $request, $estudianteId)
    {
        $estudiante = Estudiante::findOrFail($estudianteId);

        $validated = $request->validate([
            'estado' => 'required|in:activo,suspendido,egresado'
        ]);

        $estadoAnterior = $estudiante->estado;
        $estudiante->estado = $validated['estado'];
        $estudiante->save();

        // Si se suspende o egresa, desactivar usuario
        if ($estudiante->user_id && in_array($validated['estado'], ['suspendido', 'egresado'])) {
            User::where('id', $estudiante->user_id)->update(['is_active' => false]);
            
            // Si egresó, también desactivar padres si no tienen otros hijos activos
            if ($validated['estado'] === 'egresado') {
                $this->checkAndDeactivateParents($estudiante);
            }
        }

        // Si se activa, reactivar usuario
        if ($estudiante->user_id && $validated['estado'] === 'activo' && $estadoAnterior !== 'activo') {
            User::where('id', $estudiante->user_id)->update(['is_active' => true]);
        }

        return response()->json([
            'message' => 'Estado del estudiante actualizado',
            'estudiante' => $estudiante
        ]);
    }

    /**
     * Desactivar padres si todos sus hijos egresaron
     */
    private function checkAndDeactivateParents($estudiante)
    {
        $padres = $estudiante->padres;

        foreach ($padres as $padre) {
            // Verificar si todos los hijos del padre egresaron
            $hijosActivos = $padre->estudiantes()
                ->where('estado', 'activo')
                ->count();

            if ($hijosActivos === 0 && $padre->user_id) {
                User::where('id', $padre->user_id)->update(['is_active' => false]);
            }
        }
    }

    // Métodos auxiliares
    private function getPersona($tipo, $id)
    {
        switch ($tipo) {
            case 'estudiante':
                return Estudiante::find($id);
            case 'docente':
                return Docente::find($id);
            case 'padre':
                return Padre::find($id);
            default:
                return null;
        }
    }

    private function getNombreCompleto($persona, $tipo)
    {
        if ($tipo === 'estudiante') {
            return "{$persona->nombres} {$persona->apellido_paterno} {$persona->apellido_materno}";
        }
        return $persona->nombre_completo;
    }
}
