<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partido extends Model
{
    protected $fillable = [
        'eleccion_id',
        'nombre',
        'siglas',
        'descripcion',
        'logo',
        'color',
    ];

    /**
     * Relación: Un partido pertenece a una elección
     */
    public function eleccion()
    {
        return $this->belongsTo(Eleccion::class);
    }

    /**
     * Relación: Un partido tiene muchos candidatos
     */
    public function candidatos()
    {
        return $this->hasMany(Candidato::class);
    }

    /**
     * Obtener total de votos del partido
     */
    public function totalVotos()
    {
        return $this->candidatos()->withCount('votos')->get()->sum('votos_count');
    }
}
