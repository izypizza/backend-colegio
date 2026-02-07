<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Service para cálculo de estadísticas de asistencia
 * Elimina 40 líneas de código duplicado en 4 controladores
 */
class EstadisticasAsistenciaService
{
    /**
     * Calcula estadísticas de asistencia a partir de una colección
     * 
     * @param Collection $asistencias
     * @return array
     */
    public function calcular(Collection $asistencias): array
    {
        $total = $asistencias->count();
        
        if ($total === 0) {
            return [
                'total' => 0,
                'presente' => 0,
                'tarde' => 0,
                'ausente' => 0,
                'justificado' => 0,
                'porcentaje_asistencia' => 0,
                'porcentaje_ausencia' => 0,
            ];
        }

        $presente = $asistencias->where('estado', 'presente')->count();
        $tarde = $asistencias->where('estado', 'tarde')->count();
        $ausente = $asistencias->where('estado', 'ausente')->count();
        $justificado = $asistencias->where('estado', 'justificado')->count();

        // Considerar "tarde" como asistencia para el porcentaje
        $porcentajeAsistencia = round((($presente + $tarde) / $total) * 100, 2);
        $porcentajeAusencia = round(($ausente / $total) * 100, 2);

        return [
            'total' => $total,
            'presente' => $presente,
            'tarde' => $tarde,
            'ausente' => $ausente,
            'justificado' => $justificado,
            'porcentaje_asistencia' => $porcentajeAsistencia,
            'porcentaje_ausencia' => $porcentajeAusencia,
        ];
    }

    /**
     * Calcula estadísticas agrupadas por estudiante
     * 
     * @param Collection $asistencias
     * @return array
     */
    public function calcularPorEstudiante(Collection $asistencias): array
    {
        return $asistencias->groupBy('estudiante_id')->map(function ($grupo) {
            return $this->calcular($grupo);
        })->toArray();
    }

    /**
     * Calcula estadísticas agrupadas por materia
     * 
     * @param Collection $asistencias
     * @return array
     */
    public function calcularPorMateria(Collection $asistencias): array
    {
        return $asistencias->groupBy('materia_id')->map(function ($grupo) {
            return $this->calcular($grupo);
        })->toArray();
    }
}
