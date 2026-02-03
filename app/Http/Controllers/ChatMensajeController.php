<?php

namespace App\Http\Controllers;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use Illuminate\Http\Request;

class ChatMensajeController extends Controller
{
    public function index(Request $request, int $conversacionId)
    {
        $conversacion = ChatConversacion::with(['docente', 'padre'])->findOrFail($conversacionId);
        $user = $request->user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversacion)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $mensajes = ChatMensaje::where('conversacion_id', $conversacionId)
            ->with('user')
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($mensajes);
    }

    public function store(Request $request, int $conversacionId)
    {
        $validated = $request->validate([
            'mensaje' => 'required|string',
        ]);

        $conversacion = ChatConversacion::with(['docente', 'padre'])->findOrFail($conversacionId);
        $user = $request->user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversacion)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Admin solo puede visualizar, no enviar mensajes
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Los administradores solo pueden visualizar conversaciones'], 403);
        }

        $mensaje = ChatMensaje::create([
            'conversacion_id' => $conversacion->id,
            'user_id' => $user->id,
            'mensaje' => $validated['mensaje'],
        ]);

        $conversacion->update(['ultimo_mensaje_at' => now()]);

        return response()->json($mensaje, 201);
    }

    private function usuarioPuedeVerConversacion($user, $conversacion): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->docente && $conversacion->docente_id === $user->docente->id) {
            return true;
        }

        if ($user->padre && $conversacion->padre_id === $user->padre->id) {
            return true;
        }

        return false;
    }
}
