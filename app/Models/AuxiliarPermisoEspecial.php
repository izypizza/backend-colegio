<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuxiliarPermisoEspecial extends Model
{
    protected $table = 'auxiliar_permisos_especiales';

    protected $fillable = [
        'user_id',
        'puede_editar_estudiantes',
        'puede_editar_asistencias',
        'puede_editar_calificaciones',
        'activado_hasta',
        'activado_por',
        'motivo'
    ];

    protected $casts = [
        'puede_editar_estudiantes' => 'boolean',
        'puede_editar_asistencias' => 'boolean',
        'puede_editar_calificaciones' => 'boolean',
        'activado_hasta' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activadorUser()
    {
        return $this->belongsTo(User::class, 'activado_por');
    }

    // Verificar si el permiso está activo
    public function estaActivo()
    {
        if (!$this->activado_hasta) {
            return true; // Sin fecha de expiración
        }
        return now()->lte($this->activado_hasta);
    }
}
