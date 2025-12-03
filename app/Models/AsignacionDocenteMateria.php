<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionDocenteMateria extends Model
{
    protected $table = 'asignacion_docente_materia';

    protected $fillable = [
        'docente_id',
        'materia_id',
        'seccion_id',
        'periodo_academico_id'
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }
}
