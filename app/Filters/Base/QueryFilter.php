<?php

namespace App\Filters\Base;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orquestador de filtros para los controladores AJAX que aún no migraron
 * a Livewire (`app/Livewire/Base/DataTable`, REQ-0.1).
 *
 * Entrada sin cambios — sigue construyéndose con el Request y aplicándose
 * con un solo argumento (`(new XxxFilters($request))->apply($query)`),
 * así ningún controlador existente se rompe. Internamente adapta cada
 * filtro hijo al contrato nuevo de FilterInterface (REQ-0.3), pasándole
 * el valor ya leído del Request en vez de que el filtro lo lea por sí mismo.
 */
abstract class QueryFilter
{
    protected Request $request;
    protected Builder $query;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $query): Builder
    {
        $this->query = $query;

        foreach ($this->filters() as $key => $filterClass) {
            if ($this->request->filled($key)) {
                (new $filterClass())->apply($this->query, $this->request->input($key));
            }
        }

        return $this->query;
    }

    /**
     * Mapa: request_key => FilterClass
     */
    abstract protected function filters(): array;
}
