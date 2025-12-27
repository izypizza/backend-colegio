<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidato extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleccion_id',
        'estudiante_id',
        'cargo',
        'propuestas',
    ];

    /**
     * Relación: Un candidato pertenece a una elección
     */
    public function eleccion()
    {
        return $this->belongsTo(Eleccion::class, 'eleccion_id');
    }

    /**
     * Relación: Un candidato es un estudiante
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    /**
     * Relación: Un candidato tiene muchos votos
     */
    public function votos()
    {
        return $this->hasMany(Voto::class, 'candidato_id');
    }
}
