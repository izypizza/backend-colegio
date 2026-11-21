<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionClase extends Model
{
    protected $table = 'sesiones_clase';

    public const ESTADOS = ['programada', 'dictada', 'anulada'];

    protected $fillable = [
        'asignacion_docente_materia_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'tema',
        'estado',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionDocenteMateria::class, 'asignacion_docente_materia_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(AsistenciaSesion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $hasta));
    }

    public function getMateriaAttribute(): ?Materia
    {
        return $this->asignacion?->materia;
    }

    public function getSeccionAttribute(): ?Seccion
    {
        return $this->asignacion?->seccion;
    }

    public function getDocenteAttribute(): ?Docente
    {
        return $this->asignacion?->docente;
    }
}
