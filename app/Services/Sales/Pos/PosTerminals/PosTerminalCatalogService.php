<?php

namespace App\Services\Sales\Pos\PosTerminals;

use App\Models\Clients\Client;
use App\Models\Inventory\Warehouse;
use App\Models\Accounting\AccountingAccount;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Ncf\NcfSequence;

class PosTerminalCatalogService
{
    /**
     * Datos para el formulario de Creación/Edición de Terminales POS.
     * Alimenta los selectores de Almacén, Cuentas Contables, NCF y Clientes.
     */
    public function getForForm(): array
    {
        // Obtenemos la configuración global una sola vez
        $globalConfig = \App\Models\Sales\Pos\PosSetting::getSettings();
        $modoFiscal = general_config()?->esModoFiscal();
        
        return [
            // 1. Almacenes: Para vincular el stock a la terminal
            'warehouses' => Warehouse::select('id', 'name', 'type')
                ->orderBy('name')
                ->get(),

            // 3. Tipos de NCF: Solo aquellos que tienen secuencias activas y disponibles
            'ncf_types' => $modoFiscal ? NcfType::whereHas('sequences', function($q) {
                    $q->where('status', NcfSequence::STATUS_ACTIVE)
                      ->where('expiry_date', '>=', now())
                      ->whereColumn('current', '<', 'to');
                })
                ->select('id', 'name', 'code')
                ->get()
                ->map(fn($type) => [
                    'id'   => $type->id,
                    'name' => "[{$type->code}] {$type->name}",
                ])->values() : [],

            'global_client_name' => $globalConfig->defaultCustomer?->name ?? 'Consumidor Final',
            'global_printer_format' => $globalConfig->receipt_size,

            // 4. Clientes por defecto: Priorizando Consumidor Final
            'clients' => Client::select('id', 'name', 'tax_id')
                ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
                ->map(fn($client) => [
                    'id'     => $client->id,
                    'name'   => $client->name,
                    'tax_id' => $client->tax_id ?? 'N/A',
                ]),

            // 5. Formatos de impresión soportados por el sistema
            'printer_formats' => [
                ['id' => '80mm', 'name' => 'Térmica 80mm (Estándar)'],
                ['id' => '58mm', 'name' => 'Térmica 58mm (Portátil/Sunmi)'],
            ],
        ];
    }
}