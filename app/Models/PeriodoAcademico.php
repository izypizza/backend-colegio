<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';

    protected $fillable = [
        'nombre',
        'anio',
        'estado'
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }
}
