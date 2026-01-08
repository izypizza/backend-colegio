<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'dni',
        'fecha_nacimiento',
        'direccion',
        'telefono',
        'seccion_id',
        'estado'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date'
    ];

    protected $appends = ['nombre_completo', 'nombre', 'apellido', 'codigo'];

    /**
     * Accessor para nombre completo en formato: Apellido Paterno Apellido Materno, Nombres
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}");
    }

    /**
     * Accessor para compatibilidad frontend - nombre
     */
    public function getNombreAttribute(): string
    {
        return $this->nombres;
    }

    /**
     * Accessor para compatibilidad frontend - apellido completo
     */
    public function getApellidoAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno}");
    }

    /**
     * Accessor para código de estudiante (ID formateado)
     */
    public function getCodigoAttribute(): string
    {
        return 'EST-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
