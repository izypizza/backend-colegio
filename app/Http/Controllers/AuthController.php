<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Su cuenta está desactivada. Contacte al administrador.'],
            ]);
        }

        // Verificar estado del estudiante si el usuario es estudiante
        if ($user->role === 'estudiante') {
            $estudiante = $user->estudiante;
            
            if ($estudiante && $estudiante->estado === 'suspendido') {
                throw ValidationException::withMessages([
                    'email' => ['Su cuenta está suspendida. Contacte al administrador.'],
                ]);
            }
            
            if ($estudiante && $estudiante->estado === 'egresado') {
                throw ValidationException::withMessages([
                    'email' => ['Ya no puede acceder al sistema. Ha egresado del colegio.'],
                ]);
            }
        }

        // Crear token de acceso
        $token = $user->createToken('auth-token')->plainTextToken;

        // Construir datos del usuario con relaciones
        $userData = [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'admin',
            'avatar' => $user->avatar,
            'isActive' => $user->is_active ?? true,
            'createdAt' => $user->created_at->toISOString(),
        ];

        // Incluir relaciones según el rol
        if ($user->role === 'docente' && $user->docente) {
            $userData['docente'] = [
                'id' => $user->docente->id,
                'nombres' => $user->docente->nombres,
                'apellido_paterno' => $user->docente->apellido_paterno,
                'apellido_materno' => $user->docente->apellido_materno,
                'especialidad' => $user->docente->especialidad,
            ];
        } elseif ($user->role === 'padre' && $user->padre) {
            $userData['padre'] = [
                'id' => $user->padre->id,
                'nombres' => $user->padre->nombres,
                'apellido_paterno' => $user->padre->apellido_paterno,
                'apellido_materno' => $user->padre->apellido_materno,
            ];
        } elseif ($user->role === 'estudiante' && $user->estudiante) {
            $userData['estudiante'] = [
                'id' => $user->estudiante->id,
                'nombres' => $user->estudiante->nombres,
                'apellido_paterno' => $user->estudiante->apellido_paterno,
                'apellido_materno' => $user->estudiante->apellido_materno,
                'codigo' => $user->estudiante->codigo,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token,
                'refreshToken' => $token, // Por ahora usamos el mismo token
                'user' => $userData,
            ],
        ]);
    }

    /**
     * Logout de usuario
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

    /**
     * Registrar nuevo usuario (opcional)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }
}
