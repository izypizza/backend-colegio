<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestamoLibro extends Model
{
    use HasFactory;

    protected $table = 'prestamos_libros';

    protected $fillable = [
        'libro_id',
        'estudiante_id',
        'user_id',
        'fecha_prestamo',
        'fecha_devolucion',
        'devuelto',
        'estado',
        'aprobado_por',
        'fecha_respuesta',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_devolucion' => 'date',
        'fecha_respuesta' => 'datetime',
        'devuelto' => 'boolean',
    ];

    /**
     * Relación: Un préstamo pertenece a un libro
     */
    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    /**
     * Relación: Un préstamo pertenece a un estudiante
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    /**
     * Relación: Un préstamo pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Usuario que aprobó/rechazó
     */
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Verificar si el préstamo está activo
     */
    public function estaActivo()
    {
        return is_null($this->fecha_devolucion);
    }
}
