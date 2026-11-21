<?php

namespace App\Models;

use App\Helpers\CnebHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalificacionCompetencia extends Model
{
    protected $table = 'calificaciones_competencias';

    protected $fillable = [
        'estudiante_id',
        'matricula_id',
        'competencia_id',
        'periodo_academico_id',
        'escala',
        'nivel',
        'nota',
        'conclusion_descriptiva',
        'calculada_desde_actividades',
        'modificaciones_count',
        'ultima_modificacion',
        'registrado_por',
    ];

    protected $casts = [
        'nota' => 'decimal:2',
        'ultima_modificacion' => 'datetime',
        'modificaciones_count' => 'integer',
        'calculada_desde_actividades' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $calificacion) {
            if ($calificacion->escala === 'literal' && $calificacion->nivel) {
                $calificacion->nivel = CnebHelper::normalizarNivel($calificacion->nivel);
            }

            if (! $calificacion->matricula_id && $calificacion->estudiante_id) {
                $calificacion->matricula_id = $calificacion->matriculaDelPeriodo();
            }
        });
    }

    public function matriculaDelPeriodo(): ?int
    {
        $anio = PeriodoAcademico::whereKey($this->periodo_academico_id)->value('anio');

        if (! $anio) {
            return null;
        }

        $anioEscolarId = AnioEscolar::where('anio', $anio)->value('id');

        if (! $anioEscolarId) {
            return null;
        }

        return Matricula::query()
            ->where('estudiante_id', $this->estudiante_id)
            ->where('anio_escolar_id', $anioEscolarId)
            ->value('id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(Competencia::class);
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function materia(): ?Materia
    {
        return $this->competencia?->materia;
    }

    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasAnyRole(['admin', 'auxiliar'])) {
            return $query;
        }

        if ($usuario->isEstudiante()) {
            return $query->whereHas('estudiante', fn (Builder $estudiantes) => $estudiantes->where('user_id', $usuario->id));
        }

        if ($usuario->isPadre()) {
            return $query->whereHas('estudiante.padres', fn (Builder $padres) => $padres->where('user_id', $usuario->id));
        }

        if ($usuario->isDocente() && $usuario->docente) {
            return $query->whereExists(function ($asignaciones) use ($usuario) {
                $asignaciones->selectRaw('1')
                    ->from('asignacion_docente_materia as asignacion')
                    ->join('estudiantes as alumno', 'alumno.seccion_id', '=', 'asignacion.seccion_id')
                    ->join('competencias as competencia', 'competencia.materia_id', '=', 'asignacion.materia_id')
                    ->whereColumn('alumno.id', 'calificaciones_competencias.estudiante_id')
                    ->whereColumn('competencia.id', 'calificaciones_competencias.competencia_id')
                    ->whereColumn('asignacion.periodo_academico_id', 'calificaciones_competencias.periodo_academico_id')
                    ->where('asignacion.docente_id', $usuario->docente->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
