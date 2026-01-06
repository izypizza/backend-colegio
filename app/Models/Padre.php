<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Padre extends Model
{
    use HasFactory;

    protected $table = 'padres';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'dni',
        'email',
        'telefono',
        'direccion',
        'ocupacion'
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

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_padre');
    }
}
