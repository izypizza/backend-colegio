<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user');

        if ($request->filled('entidad')) {
            $query->where('entidad', $request->input('entidad'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->input('accion'));
        }

        return response()->json($query->orderByDesc('created_at')->paginate(30));
    }
}
