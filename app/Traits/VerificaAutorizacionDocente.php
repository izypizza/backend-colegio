<?php

namespace App\Traits;

use App\Models\Docente;
use App\Models\AsignacionDocenteMateria;
use Illuminate\Http\JsonResponse;

/**
 * Trait para verificar autorización de docentes
 * Elimina 125 líneas de código duplicado en 5 controladores
 */
trait VerificaAutorizacionDocente
{
    /**
     * Verifica que un docente tenga asignada una materia específica
     * 
     * @param int $materiaId
     * @param int|null $seccionId
     * @return bool|JsonResponse True si está autorizado, JsonResponse con error si no
     */
    protected function verificarDocenteAsignado(int $materiaId, ?int $seccionId = null)
    {
        $user = auth()->user();
        
        // Si no es docente, permitir (admin/auxiliar tienen acceso completo)
        if ($user->role !== 'docente') {
            return true;
        }

        // Obtener el docente asociado al usuario
        $docente = Docente::where('user_id', $user->id)->first();
        
        if (!$docente) {
            return response()->json([
                'error' => 'Docente no encontrado'
            ], 404);
        }

        // Construir query de asignación
        $query = AsignacionDocenteMateria::where('docente_id', $docente->id)
            ->where('materia_id', $materiaId);

        // Si se especifica sección, filtrar por ella también
        if ($seccionId) {
            $query->where('seccion_id', $seccionId);
        }

        $asignacion = $query->first();

        if (!$asignacion) {
            return response()->json([
                'error' => 'No tienes asignada esta materia' . ($seccionId ? ' en esta sección' : '')
            ], 403);
        }

        return true;
    }

    /**
     * Obtiene el docente actual autenticado
     * 
     * @return Docente|null
     */
    protected function getDocenteActual(): ?Docente
    {
        $user = auth()->user();
        
        if ($user->role !== 'docente') {
            return null;
        }

        return Docente::where('user_id', $user->id)->first();
    }

    /**
     * Verifica si el usuario actual es docente
     * 
     * @return bool
     */
    protected function esDocente(): bool
    {
        return auth()->user()->role === 'docente';
    }
}
