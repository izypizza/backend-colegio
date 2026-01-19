<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'secciones';

    protected $fillable = [
        'nombre',
        'grado_id',
        'capacidad_maxima',
        'turno',
    ];

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class);
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }
}
