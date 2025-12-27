<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'autor',
        'categoria_id',
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    /**
     * Relación: Un libro pertenece a una categoría
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaLibro::class, 'categoria_id');
    }

    /**
     * Relación: Un libro tiene muchos préstamos
     */
    public function prestamos()
    {
        return $this->hasMany(PrestamoLibro::class, 'libro_id');
    }

    /**
     * Obtener el préstamo activo del libro
     */
    public function prestamoActivo()
    {
        return $this->hasOne(PrestamoLibro::class, 'libro_id')
                    ->whereNull('fecha_devolucion');
    }
}
