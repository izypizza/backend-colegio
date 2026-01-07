<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'periodo_academico_id',
        'nota'
    ];

    protected $casts = [
        'nota' => 'decimal:2'
    ];

    protected $appends = ['periodo'];

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
