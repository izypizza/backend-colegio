<?php

namespace App\Helpers;

use App\Models\PeriodoAcademico;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Helper para manejo de períodos académicos
 * Elimina 48 líneas de código duplicado en 4 controladores
 */
class PeriodoAcademicoHelper
{
    /**
     * Obtiene el período académico actual (activo)
     * Si se proporciona un ID, lo busca directamente
     * 
     * @param int|null $periodoId
     * @return PeriodoAcademico
     * @throws ModelNotFoundException
     */
    public static function obtenerPeriodoActual(?int $periodoId = null): PeriodoAcademico
    {
        // Si se proporciona un ID específico, buscarlo
        if ($periodoId) {
            return PeriodoAcademico::findOrFail($periodoId);
        }

        // Buscar el período activo
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();

        if (!$periodo) {
            throw new ModelNotFoundException('No hay un período académico activo');
        }

        return $periodo;
    }

    /**
     * Verifica si existe un período activo
     * 
     * @return bool
     */
    public static function existePeriodoActivo(): bool
    {
        return PeriodoAcademico::where('estado', 'activo')->exists();
    }

    /**
     * Obtiene el ID del período activo o null si no existe
     * 
     * @return int|null
     */
    public static function obtenerIdPeriodoActivo(): ?int
    {
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();
        return $periodo?->id;
    }

    /**
     * Obtiene el período actual o lanza excepción con mensaje personalizado
     * 
     * @param int|null $periodoId
     * @param string $mensajeError
     * @return PeriodoAcademico
     */
    public static function obtenerPeriodoOFallar(?int $periodoId = null, string $mensajeError = 'No hay período académico activo'): PeriodoAcademico
    {
        if ($periodoId) {
            return PeriodoAcademico::findOrFail($periodoId);
        }

        $periodo = PeriodoAcademico::where('estado', 'activo')->first();

        if (!$periodo) {
            abort(400, $mensajeError);
        }

        return $periodo;
    }
}
