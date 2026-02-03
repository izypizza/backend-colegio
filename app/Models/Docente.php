<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;

    protected $table = 'docentes';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'dni',
        'email',
        'telefono',
        'direccion',
        'especialidad'
    ];

    protected $appends = ['nombre_completo'];

    /**
     * Accessor para nombre completo en formato: Apellido Paterno Apellido Materno, Nombres
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }

    /**
     * Relación: Materias que enseña el docente
     */
    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'asignacion_docente_materia')
                    ->withPivot('seccion_id', 'periodo_academico_id')
                    ->withTimestamps();
    }

    /**
     * Relación: Secciones donde enseña el docente
     */
    public function secciones()
    {
        return $this->belongsToMany(Seccion::class, 'asignacion_docente_materia')
                    ->withPivot('materia_id', 'periodo_academico_id')
                    ->withTimestamps();
    }

    public function chatConversaciones()
    {
        return $this->hasMany(ChatConversacion::class);
    }
}
