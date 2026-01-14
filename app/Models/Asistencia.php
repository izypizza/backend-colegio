<?php

namespace App\Models;

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
