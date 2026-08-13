<?php

namespace App\Services\PointOfSale;

use App\Models\Clients\BusinessType;
use App\Models\Clients\Client;
use App\Models\Geo\Province;

class POSCatalogService
{
    public function getForFilters(): array
    {
        return [
            'clients' => Client::select('id', 'name')->orderBy('name')->get(),
            'businessTypes' => BusinessType::select('id', 'nombre')->orderBy('nombre')->get(),
            'states' => Province::ordered()->select('id', 'name')->get(),
        ];
    }

    public function getForForm(): array
    {
        return [
            'clients' => Client::select('id', 'name', 'tax_id')->orderBy('name')->get(),
            'businessTypes' => BusinessType::select('id', 'nombre', 'prefix')->orderBy('nombre')->get(),
            'states' => Province::ordered()->select('id', 'name')->get(),
        ];
    }
}
