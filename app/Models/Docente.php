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
        'nombre',
        'email',
        'telefono',
        'dni',
        'direccion',
        'especialidad'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }
}
