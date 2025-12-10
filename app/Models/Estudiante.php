<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';

    protected $fillable = [
        'nombre',
        'fecha_nacimiento',
        'seccion_id'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date'
    ];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function padres()
    {
        return $this->belongsToMany(Padre::class, 'estudiante_padre');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }
}
