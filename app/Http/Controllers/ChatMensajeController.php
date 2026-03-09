<?php

namespace App\Http\Controllers;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ChatMensajeController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Listar mensajes de una conversación
     */
    public function index(Request $request, int $conversacionId)
    {
        $conversacion = ChatConversacion::with(['docente', 'padre'])->findOrFail($conversacionId);
        $user = $request->user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversacion)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Marcar mensajes como leídos
        $conversacion->marcarMensajesLeidosPara($user->id);

        $mensajes = ChatMensaje::where('conversacion_id', $conversacionId)
            ->with('user')
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($mensajes);
    }

    /**
     * Enviar mensaje en una conversación
     */
    public function store(Request $request, int $conversacionId)
    {
        $validated = $request->validate([
            'mensaje' => 'required|string|max:5000',
        ]);

        $conversacion = ChatConversacion::with(['docente.user', 'padre.user'])->findOrFail($conversacionId);
        $user = $request->user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversacion)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Admin solo puede visualizar, no enviar mensajes
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Los administradores solo pueden visualizar conversaciones'], 403);
        }

        // Crear mensaje
        $mensaje = ChatMensaje::create([
            'conversacion_id' => $conversacion->id,
            'user_id' => $user->id,
            'mensaje' => $validated['mensaje'],
            'es_sistema' => false,
        ]);

        // Actualizar timestamp de conversación
        $conversacion->update(['ultimo_mensaje_at' => now()]);

        // Enviar notificación al otro participante
        $this->notificarDestinatario($conversacion, $user);

        return response()->json($mensaje->load('user'), 201);
    }

    /**
     * Marcar mensaje como leído
     */
    public function marcarLeido(Request $request, int $conversacionId, int $mensajeId)
    {
        $conversacion = ChatConversacion::findOrFail($conversacionId);
        $user = $request->user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversacion)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $mensaje = ChatMensaje::where('conversacion_id', $conversacionId)
            ->where('id', $mensajeId)
            ->where('user_id', '!=', $user->id) // Solo puede marcar mensajes que no son suyos
            ->firstOrFail();

        $mensaje->marcarLeido();

        return response()->json(['message' => 'Mensaje marcado como leído']);
    }

    /**
     * Contar mensajes no leídos
     */
    public function contarNoLeidos(Request $request)
    {
        $user = $request->user();
        
        if ($user->docente) {
            $conversaciones = ChatConversacion::where('docente_id', $user->docente->id)->pluck('id');
        } elseif ($user->padre) {
            $conversaciones = ChatConversacion::where('padre_id', $user->padre->id)->pluck('id');
        } else {
            return response()->json(['total' => 0, 'por_conversacion' => []]);
        }

        $total = ChatMensaje::whereIn('conversacion_id', $conversaciones)
            ->where('user_id', '!=', $user->id)
            ->whereNull('leido_at')
            ->count();

        $porConversacion = ChatMensaje::whereIn('conversacion_id', $conversaciones)
            ->where('user_id', '!=', $user->id)
            ->whereNull('leido_at')
            ->selectRaw('conversacion_id, count(*) as total')
            ->groupBy('conversacion_id')
            ->pluck('total', 'conversacion_id');

        return response()->json([
            'total' => $total,
            'por_conversacion' => $porConversacion,
        ]);
    }

    /**
     * Verificar permisos
     */
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

    /**
     * Notificar al otro participante de la conversación
     */
    private function notificarDestinatario(ChatConversacion $conversacion, $remitente): void
    {
        // Determinar destinatario
        $destinatarioUserId = null;

        if ($remitente->docente && $conversacion->docente_id === $remitente->docente->id) {
            // El remitente es el docente, notificar al padre
            $destinatarioUserId = $conversacion->padre->user_id ?? null;
        } elseif ($remitente->padre && $conversacion->padre_id === $remitente->padre->id) {
            // El remitente es el padre, notificar al docente
            $destinatarioUserId = $conversacion->docente->user_id ?? null;
        }

        if ($destinatarioUserId) {
            $this->notificationService->notificarNuevoMensaje(
                $remitente->id,
                $destinatarioUserId,
                (string) $conversacion->id
            );
        }
    }
}
