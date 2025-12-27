<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voto extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleccion_id',
        'candidato_id',
        'user_id',
    ];

    /**
     * Relación: Un voto pertenece a una elección
     */
    public function eleccion()
    {
        return $this->belongsTo(Eleccion::class, 'eleccion_id');
    }

    /**
     * Relación: Un voto es para un candidato
     */
    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    /**
     * Relación: Un voto pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
