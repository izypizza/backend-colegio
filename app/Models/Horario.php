<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'seccion_id',
        'materia_id',
        'dia',
        'hora_inicio',
        'hora_fin'
    ];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}
