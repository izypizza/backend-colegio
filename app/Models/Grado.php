<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;

    protected $table = 'grados';

    protected $fillable = [
        'nombre',
        'nivel'
    ];

    public function secciones()
    {
        return $this->hasMany(Seccion::class);
    }

    public function estudiantes()
    {
        return $this->hasManyThrough(Estudiante::class, Seccion::class);
    }
}
