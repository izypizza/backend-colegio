<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Notificacion::where('user_id', $user->id)->orderByDesc('created_at');

        if ($request->boolean('no_leidas')) {
            $query->whereNull('leido_at');
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'tipo' => 'nullable|string|max:50',
            'data' => 'nullable|array',
        ]);

        $notificacion = Notificacion::create($validated);

        return response()->json($notificacion, 201);
    }

    public function marcarLeida(Request $request, int $id)
    {
        $user = $request->user();
        $notificacion = Notificacion::where('user_id', $user->id)->findOrFail($id);
        $notificacion->update(['leido_at' => now()]);

        return response()->json(['message' => 'Notificacion marcada como leida']);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $user = $request->user();
        Notificacion::where('user_id', $user->id)->whereNull('leido_at')->update(['leido_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leidas']);
    }
}
