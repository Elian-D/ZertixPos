<?php

namespace App\Services\Sales\Quotes;

use App\Models\Clients\Client;
use App\Models\Sales\Quotes\Quote;
use App\Models\User;

class QuoteCatalogService
{
    /**
     * Datos para los filtros de la tabla principal de Cotizaciones (Index).
     */
    public function getForFilters(): array
    {
        return [
            // Solo clientes que ya tienen cotizaciones para no saturar el filtro
            'customers' => Client::whereHas('quotes')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            // Usuarios/Vendedores que han realizado cotizaciones
            'users' => User::whereHas('quotes')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'statuses' => Quote::getStatuses(),

            'origins' => [
                'backoffice' => 'Administración',
                'pos' => 'POS',
            ],
        ];
    }

    /**
     * Datos para el formulario de Creación/Edición (QuoteBuilder).
     */
    public function getForForm(): array
    {
        return [
            // Clientes operativos, priorizando Consumidor Final para rapidez en el POS
            // (Fase 11, REQ-11.6: solo clientes activos pueden cotizar)
            'customers' => Client::where('is_active', true)
                ->select('id', 'name', 'tax_id', 'email')
                ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
                ->map(fn ($client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'tax_id' => $client->tax_id ?? 'N/A',
                    'email' => $client->email,
                ]),
            // Corregimos la carga de productos para usar InventoryStock y el nombre de columna 'price'
            'products' => \App\Models\Inventory\InventoryStock::with(['product' => function ($query) {
                $query->select('id', 'name', 'price', 'sku');
            }])
                ->where('quantity', '>', 0)
                ->get()
                ->filter(fn ($stock) => $stock->product !== null)
                ->map(function ($stock) {
                    return [
                        'id' => $stock->product_id,
                        'name' => $stock->product->name,
                        'price' => $stock->product->price, // Cambiado de sale_price a price
                        'sku' => $stock->product->sku,
                        'stock' => $stock->quantity,
                    ];
                })->values()->toArray(),

            // Configuraciones de validez (Días por defecto para la fecha de vencimiento)
            'validity_options' => [
                ['days' => 7,  'label' => '7 Días'],
                ['days' => 15, 'label' => '15 Días'],
                ['days' => 30, 'label' => '30 Días (Mes)'],
            ],

            'default_expiration' => now()->addDays(15)->format('Y-m-d'),
        ];
    }
}
