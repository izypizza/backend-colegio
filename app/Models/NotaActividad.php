<?php

namespace App\Models;

use App\Helpers\CnebHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaActividad extends Model
{
    protected $table = 'notas_actividad';

    protected $fillable = [
        'actividad_evaluacion_id',
        'estudiante_id',
        'matricula_id',
        'nota',
        'nivel',
        'observacion',
    ];

    protected $casts = [
        'nota' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $registro) {
            if ($registro->nota !== null) {
                $registro->nivel = CnebHelper::nivelDesdeNota((float) $registro->nota);
            }
        });
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(ActividadEvaluacion::class, 'actividad_evaluacion_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }
}
