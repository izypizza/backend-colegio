<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = [
        'nombre'
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    /**
     * Relación: Docentes que enseñan esta materia
     */
    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'asignacion_docente_materia')
                    ->withPivot('seccion_id', 'periodo_academico_id')
                    ->withTimestamps();
    }

    /**
     * Relación: Secciones donde se enseña esta materia
     */
    public function secciones()
    {
        return $this->belongsToMany(Seccion::class, 'asignacion_docente_materia')
                    ->withPivot('docente_id', 'periodo_academico_id')
                    ->withTimestamps();
    }
}
