<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaSesion extends Model
{
    protected $table = 'asistencias_sesion';

    public const ESTADOS = ['presente', 'tarde', 'ausente', 'justificado', 'permiso'];

    protected $fillable = [
        'sesion_clase_id',
        'estudiante_id',
        'matricula_id',
        'estado',
        'minutos_tardanza',
        'justificacion',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionClase::class, 'sesion_clase_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function cuentaComoAsistencia(): bool
    {
        return in_array($this->estado, ['presente', 'tarde', 'justificado', 'permiso'], true);
    }
}
