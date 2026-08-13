<?php

namespace App\Services\Accounting\DocumentType;

class DocumentTypeCatalogService
{
    /**
     * Datos para los filtros de la tabla index.
     */
    public function getForFilters(): array
    {
        return [
            'active_options' => [
                '1' => 'Activos',
                '0' => 'Inactivos',
            ],
        ];
    }
}
