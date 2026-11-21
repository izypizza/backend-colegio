<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competencia extends Model
{
    protected $table = 'competencias';

    protected $fillable = [
        'materia_id',
        'ciclo',
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(CalificacionCompetencia::class);
    }

    public function scopeDelCiclo(Builder $query, ?string $ciclo): Builder
    {
        return $ciclo ? $query->where('ciclo', $ciclo) : $query;
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('codigo');
    }
}
