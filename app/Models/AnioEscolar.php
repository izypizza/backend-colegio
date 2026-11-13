<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnioEscolar extends Model
{
    use HasFactory;

    protected $table = 'anios_escolares';

    protected $fillable = [
        'anio',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'anio' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function periodosAcademicos(): HasMany
    {
        return $this->hasMany(PeriodoAcademico::class, 'anio', 'anio');
    }

    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->where('anio', $anio);
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->where('estado', 'abierto');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    /**
     * Un año cerrado es historial inmutable. Tanto la planificación del año
     * siguiente como el año en curso permiten registrar matrícula.
     */
    public function permiteMatricula(): bool
    {
        return $this->estado !== 'cerrado';
    }

    public static function vigente(): ?self
    {
        return self::query()
            ->where('estado', 'abierto')
            ->orderByDesc('anio')
            ->first();
    }
}
