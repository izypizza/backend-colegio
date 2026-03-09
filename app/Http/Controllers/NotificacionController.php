<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Listar notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Notificacion::where('user_id', $user->id)->orderByDesc('created_at');

        // Filtrar por tipo
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Filtrar por prioridad
        if ($request->has('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        // Solo no leídas
        if ($request->boolean('no_leidas')) {
            $query->whereNull('leido_at');
        }

        $notificaciones = $query->paginate(20);

        return response()->json($notificaciones);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function estadisticas(Request $request)
    {
        $user = $request->user();
        $stats = $this->notificationService->estadisticas($user->id);

        return response()->json($stats);
    }

    /**
     * Crear notificación (solo admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'rol' => 'nullable|string|in:admin,auxiliar,docente,estudiante,padre',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'tipo' => 'nullable|string|in:mensaje,eleccion,calificacion,asistencia,comunicado,evento,alerta,info',
            'accion_url' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        $tipo = $validated['tipo'] ?? 'info';
        $titulo = $validated['titulo'];
        $mensaje = $validated['mensaje'];
        $data = $validated['data'] ?? null;
        $accionUrl = $validated['accion_url'] ?? null;

        // Envío masivo por rol
        if (!empty($validated['rol'])) {
            $count = $this->notificationService->crearPorRol(
                $validated['rol'],
                $titulo,
                $mensaje,
                $tipo,
                $data,
                $accionUrl
            );

            return response()->json([
                'message' => "Se enviaron {$count} notificaciones al rol {$validated['rol']}",
                'count' => $count
            ], 201);
        }

        // Envío masivo por IDs
        if (!empty($validated['user_ids'])) {
            $count = $this->notificationService->crearMasivo(
                $validated['user_ids'],
                $titulo,
                $mensaje,
                $tipo,
                $data,
                $accionUrl
            );

            return response()->json([
                'message' => "Se enviaron {$count} notificaciones",
                'count' => $count
            ], 201);
        }

        // Envío individual
        if (!empty($validated['user_id'])) {
            $notificacion = $this->notificationService->crear(
                $validated['user_id'],
                $titulo,
                $mensaje,
                $tipo,
                $data,
                $accionUrl
            );

            return response()->json($notificacion, 201);
        }

        return response()->json(['message' => 'Debe especificar user_id, user_ids o rol'], 422);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida(Request $request, int $id)
    {
        $user = $request->user();
        $notificacion = Notificacion::where('user_id', $user->id)->findOrFail($id);
        $notificacion->marcarLeida();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(Request $request)
    {
        $user = $request->user();
        $count = Notificacion::where('user_id', $user->id)
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas',
            'count' => $count
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $notificacion = Notificacion::where('user_id', $user->id)->findOrFail($id);
        $notificacion->delete();

        return response()->json(['message' => 'Notificación eliminada']);
    }

    /**
     * Eliminar todas las notificaciones leídas
     */
    public function eliminarLeidas(Request $request)
    {
        $user = $request->user();
        $count = Notificacion::where('user_id', $user->id)
            ->whereNotNull('leido_at')
            ->delete();

        return response()->json([
            'message' => 'Notificaciones leídas eliminadas',
            'count' => $count
        ]);
    }
}
