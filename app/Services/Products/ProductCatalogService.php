<?php

namespace App\Services\Products;

use App\Models\Products\Category;
use App\Models\Products\Unit;

class ProductCatalogService
{
    /**
     * Datos para los filtros de la tabla
     */
    public function getForFilters(): array
    {
        return [
            'categories' => Category::activos()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'units' => Unit::activos()
                ->select('id', 'name', 'abbreviation')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Datos para el formulario de Create/Edit
     */
    public function getForForm(): array
    {
        // En este caso, como los filtros y el formulario usan lo mismo,
        // podrías incluso llamar a getForFilters() para no repetir código.
        // Solo impuestos scope 'product' — 'propina_legal' (scope 'sale', REQ-5.7
        // diferida) no se asigna por producto, y 'default' no es un impuesto en sí,
        // es la clave preseleccionada al crear (Fase 5, REQ-5.4).
        $productTaxes = collect(config('impuestos'))
            ->filter(fn ($tax) => is_array($tax) && ($tax['scope'] ?? null) === 'product');

        return array_merge($this->getForFilters(), [
            // Split en dos grupos para la UI (Fase 5, REQ-5.4 extra — regla DGII):
            // ITBIS es mutuamente excluyente (radio buttons), el resto es aditivo
            // y se apila libremente (checkboxes) — ver comentario en config/impuestos.php.
            'itbisTaxes' => $productTaxes->filter(fn ($tax) => ($tax['group'] ?? null) === 'itbis'),
            'addonTaxes' => $productTaxes->reject(fn ($tax) => ($tax['group'] ?? null) === 'itbis'),
        ]);
    }
}