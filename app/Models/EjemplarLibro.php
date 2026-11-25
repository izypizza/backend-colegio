<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EjemplarLibro extends Model
{
    use HasFactory;

    protected $table = 'ejemplares_libros';

    protected $fillable = [
        'libro_id',
        'codigo',
        'estado',
    ];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function prestamos()
    {
        return $this->hasMany(PrestamoLibro::class, 'ejemplar_id');
    }

    public function prestamoActivo()
    {
        return $this->hasOne(PrestamoLibro::class, 'ejemplar_id')
            ->where('estado', 'aprobado')
            ->where('devuelto', false);
    }
}
