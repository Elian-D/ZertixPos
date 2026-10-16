<?php

namespace App\Services\Equipment;

use App\Models\Clients\EquipmentType;
use App\Models\Clients\PointOfSale;

class EquipmentCatalogService
{
    /**
     * Datos para filtros del index
     */
    public function getForFilters(): array
    {
        return [
            'equipmentTypes' => EquipmentType::select('id', 'nombre')
                ->orderBy('nombre')
                ->get(),

            'pointsOfSale' => PointOfSale::select('id', 'name')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Datos para formularios Create / Edit
     */
    public function getForForm(): array
    {
        return [
            'equipmentTypes' => EquipmentType::activos()
                ->select('id', 'nombre', 'prefix')
                ->orderBy('nombre')
                ->get(),

            'pointsOfSale' => PointOfSale::where('active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ];
    }
}
