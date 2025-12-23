<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Padre extends Model
{
    use HasFactory;

    protected $table = 'padres';

    protected $fillable = [
        'user_id',
        'nombre',
        'email',
        'telefono',
        'dni',
        'direccion',
        'ocupacion'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_padre');
    }
}
