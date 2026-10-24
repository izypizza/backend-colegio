<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'fecha',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Estados válidos de asistencia
     */
    const ESTADO_PRESENTE = 'presente';
    const ESTADO_TARDE = 'tarde';
    const ESTADO_AUSENTE = 'ausente';

    /**
     * Accessor para compatibilidad con código anterior
     */
    public function getPresenteAttribute()
    {
        return $this->estado === self::ESTADO_PRESENTE || $this->estado === self::ESTADO_TARDE;
    }

    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasAnyRole(['admin', 'auxiliar'])) {
            return $query;
        }

        if ($usuario->isEstudiante()) {
            return $query->whereHas('estudiante', fn (Builder $estudiantes) =>
                $estudiantes->where('user_id', $usuario->id));
        }

        if ($usuario->isPadre()) {
            return $query->whereHas('estudiante.padres', fn (Builder $padres) =>
                $padres->where('user_id', $usuario->id));
        }

        if ($usuario->isDocente() && $usuario->docente) {
            return $query->whereExists(function ($asignaciones) use ($usuario) {
                $asignaciones->selectRaw('1')
                    ->from('asignacion_docente_materia as asignacion')
                    ->join('estudiantes as alumno', 'alumno.seccion_id', '=', 'asignacion.seccion_id')
                    ->whereColumn('alumno.id', 'asistencias.estudiante_id')
                    ->whereColumn('asignacion.materia_id', 'asistencias.materia_id')
                    ->where('asignacion.docente_id', $usuario->docente->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }
}
