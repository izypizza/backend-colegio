<?php

namespace App\Http\Controllers;

use App\Models\ChatConversacion;
use Illuminate\Http\Request;

class ChatConversacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ChatConversacion::with(['docente', 'padre', 'estudiante'])
            ->orderByDesc('ultimo_mensaje_at');

        // Admin puede ver todas las conversaciones
        if ($user->role === 'admin') {
            // No aplicar filtro, admin ve todo
        } elseif ($user->docente) {
            $query->where('docente_id', $user->docente->id);
        } elseif ($user->padre) {
            $query->where('padre_id', $user->padre->id);
        } else {
            // Si no es admin, docente o padre, no puede ver conversaciones
            return response()->json(['data' => [], 'total' => 0]);
        }

        $conversaciones = $query->paginate(20);

        return response()->json($conversaciones);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Solo docentes o padres pueden iniciar conversaciones'], 403);
        }

        // Forzar que el usuario autenticado sea parte de la conversación
        if ($user->docente) {
            $validated = $request->validate([
                'padre_id' => 'required|exists:padres,id',
                'estudiante_id' => 'nullable|exists:estudiantes,id',
            ]);

            $validated['docente_id'] = $user->docente->id;
        } elseif ($user->padre) {
            $validated = $request->validate([
                'docente_id' => 'required|exists:docentes,id',
                'estudiante_id' => 'nullable|exists:estudiantes,id',
            ]);

            $validated['padre_id'] = $user->padre->id;

            // Si se indica estudiante, validar que pertenezca al padre
            if (!empty($validated['estudiante_id'])) {
                $tieneHijo = $user->padre->estudiantes()
                    ->where('estudiantes.id', $validated['estudiante_id'])
                    ->exists();

                if (!$tieneHijo) {
                    return response()->json([
                        'message' => 'El estudiante no pertenece a este padre'], 422);
                }
            }
        } else {
            return response()->json(['message' => 'Rol no autorizado para iniciar chat'], 403);
        }

        $conversacion = ChatConversacion::firstOrCreate(
            $validated,
            ['ultimo_mensaje_at' => now()]
        );

        return response()->json($conversacion->load(['docente', 'padre', 'estudiante']), 201);
    }

    /**
     * Ver todas las conversaciones (solo admin)
     */
    public function todas(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $conversaciones = ChatConversacion::with([
            'docente',
            'padre',
            'estudiante',
            'mensajes' => function($query) {
                $query->latest()->limit(1);
            }
        ])
        ->withCount('mensajes')
        ->orderByDesc('ultimo_mensaje_at')
        ->paginate(50);

        return response()->json($conversaciones);
    }

    /**
     * Estadísticas de chat (solo admin)
     */
    public function estadisticas(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $stats = [
            'total_conversaciones' => ChatConversacion::count(),
            'conversaciones_activas' => ChatConversacion::where('ultimo_mensaje_at', '>=', now()->subDays(7))->count(),
            'total_mensajes' => \DB::table('chat_mensajes')->count(),
            'mensajes_hoy' => \DB::table('chat_mensajes')->whereDate('created_at', today())->count(),
            'docentes_participantes' => ChatConversacion::distinct('docente_id')->count('docente_id'),
            'padres_participantes' => ChatConversacion::distinct('padre_id')->count('padre_id'),
        ];

        return response()->json($stats);
    }
}
