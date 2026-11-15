<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatriculaApoderado extends Model
{
    protected $table = 'matricula_apoderado';

    public const TIPOS = ['padre', 'madre', 'apoderado'];

    protected $fillable = [
        'matricula_id',
        'padre_id',
        'tipo',
        'es_contacto_principal',
    ];

    protected $casts = [
        'es_contacto_principal' => 'boolean',
    ];

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Padre::class);
    }

    public function scopePrincipales(Builder $query): Builder
    {
        return $query->where('es_contacto_principal', true);
    }
}
