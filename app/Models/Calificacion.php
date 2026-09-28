<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'periodo_academico_id',
        'nota',
        'observaciones',
        'modificaciones_count',
        'ultima_modificacion'
    ];

    protected $casts = [
        'nota' => 'decimal:2',
        'ultima_modificacion' => 'datetime'
    ];

    protected $appends = ['periodo'];

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
                    ->whereColumn('alumno.id', 'calificaciones.estudiante_id')
                    ->whereColumn('asignacion.periodo_academico_id', 'calificaciones.periodo_academico_id')
                    ->where('asignacion.docente_id', $usuario->docente->id)
                    ->where(function ($alcance) {
                        $alcance->whereColumn('asignacion.materia_id', 'calificaciones.materia_id')
                            ->orWhere(function ($tutoria) {
                                $tutoria->where('asignacion.es_tutor', true)
                                    ->where(function ($vigencia) {
                                        $vigencia->whereNull('asignacion.tutor_hasta')
                                            ->orWhere('asignacion.tutor_hasta', '>=', now());
                                    });
                            });
                    });
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

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    /**
     * Accessor para compatibilidad con frontend
     */
    public function getPeriodoAttribute()
    {
        return $this->periodoAcademico;
    }
}
