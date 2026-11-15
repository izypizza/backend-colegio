<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'entidad' => 'nullable|string|max:60',
            'accion' => 'nullable|in:crear,actualizar,eliminar',
            'user_id' => 'nullable|integer|exists:users,id',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'buscar' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $query = AuditLog::query()->with('user:id,name,role,email');

        if ($request->filled('entidad')) {
            $query->where('entidad', $request->input('entidad'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->input('accion'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        if ($request->filled('buscar')) {
            $termino = '%' . $request->input('buscar') . '%';
            $query->where(function ($subconsulta) use ($termino) {
                $subconsulta->where('descripcion', 'like', $termino)
                    ->orWhere('entidad', 'like', $termino)
                    ->orWhere('ip', 'like', $termino);
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 30))
        );
    }

    /**
     * Entidades y usuarios presentes en el registro, para los filtros.
     */
    public function filtros()
    {
        return response()->json([
            'entidades' => AuditLog::query()
                ->select('entidad')
                ->distinct()
                ->orderBy('entidad')
                ->pluck('entidad'),
            'usuarios' => AuditLog::query()
                ->with('user:id,name,role')
                ->select('user_id')
                ->distinct()
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('id')
                ->values(),
        ]);
    }
}
