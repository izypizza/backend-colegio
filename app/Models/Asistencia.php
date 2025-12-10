<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'fecha',
        'presente'
    ];

    protected $casts = [
        'fecha' => 'date',
        'presente' => 'boolean'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}
