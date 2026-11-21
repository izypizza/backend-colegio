<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActividadEvaluacion extends Model
{
    protected $table = 'actividades_evaluacion';

    public const TIPOS = ['examen', 'tarea', 'practica', 'proyecto', 'participacion', 'otro'];

    protected $fillable = [
        'competencia_id',
        'periodo_academico_id',
        'nombre',
        'tipo',
        'peso',
        'fecha',
        'orden',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
        'fecha' => 'date',
        'orden' => 'integer',
    ];

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(Competencia::class);
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(NotaActividad::class);
    }
}
