<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para paginación opcional en controladores
 * Elimina 48 líneas de código duplicado en 4 controladores
 */
trait ConPaginacionOpcional
{
    /**
     * Pagina o retorna todos los resultados según el parámetro 'all' en el request
     * 
     * @param Builder $query
     * @param Request $request
     * @param int $perPageDefault Cantidad por defecto de items por página
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    protected function paginateOrAll(Builder $query, Request $request, int $perPageDefault = 50)
    {
        // Si se solicita todo sin paginación
        if ($request->has('all') && $request->input('all') === 'true') {
            return $query->get();
        }

        // Paginación normal
        $perPage = $request->input('per_page', $perPageDefault);
        return $query->paginate($perPage);
    }

    /**
     * Aplica paginación con verificación de parámetros
     * 
     * @param Builder $query
     * @param Request $request
     * @return mixed
     */
    protected function aplicarPaginacion(Builder $query, Request $request)
    {
        return $this->paginateOrAll($query, $request);
    }
}
