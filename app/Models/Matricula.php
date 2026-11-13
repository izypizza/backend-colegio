<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    public const ESTADOS = ['matriculado', 'retirado', 'trasladado', 'culminado'];

    public const TIPOS = ['regular', 'repite', 'traslado_externo', 'reincorporacion'];

    protected $fillable = [
        'estudiante_id',
        'anio_escolar_id',
        'seccion_id',
        'estado',
        'tipo',
        'origen',
        'fecha_matricula',
        'observacion',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function anioEscolar(): BelongsTo
    {
        return $this->belongsTo(AnioEscolar::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', 'matriculado');
    }

    public function scopeDelAnioEscolar(Builder $query, int $anioEscolarId): Builder
    {
        return $query->where('anio_escolar_id', $anioEscolarId);
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'matriculado';
    }
}
