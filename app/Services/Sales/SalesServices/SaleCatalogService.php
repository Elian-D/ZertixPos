<?php

namespace App\Services\Sales\SalesServices;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\DocumentType;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;

class SaleCatalogService
{
    /**
     * Datos para los filtros de la tabla principal de Ventas.
     */
    public function getForFilters(): array
    {
        return [
            'clients' => Client::whereHas('sales')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'warehouses' => Warehouse::select('id', 'name')
                ->orderBy('name')
                ->get(),

            'payment_types' => Sale::getPaymentTypes(),

            'tipo_pagos' => TipoPago::sortByPriority(
                TipoPago::activo()->select('id', 'nombre', 'slug')->get()
            ),

            // Sesiones activas o recientes (últimos 30 días) para evitar saturación
            'pos_sessions' => PosSession::where('created_at', '>=', now()->subDays(30))
                ->orderBy('id', 'desc')
                ->pluck('id', 'id'), // O un formato más legible como "Sesión #ID (Fecha)"

            'pos_terminals' => PosTerminal::select('id', 'name')
                ->get()
                ->pluck('name', 'id'),

            'statuses' => Sale::getStatuses(),
        ];
    }

    /**
     * Datos para el formulario de Venta (Ventanilla o POS).
     */
    public function getForForm(): array
    {
        return [
            // 1. Clientes: Priorizando Consumidor Final — solo clientes operables
            // (Fase 11, REQ-11.6: is_active reemplaza al estado de categoría;
            // moroso es un cálculo aparte, ver Client::esMoroso()).
            'clients' => Client::where('is_active', true)
                // AGREGAMOS 'tax_id' AQUÍ ABAJO:
                ->select('id', 'name', 'tax_id', 'credit_limit', 'balance', 'is_active')
                ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'tax_id' => $client->tax_id, // Ahora esto ya no será null
                        'credit_limit' => $client->credit_limit,
                        'balance' => $client->balance,
                        'available' => $client->credit_limit - $client->balance,
                        'is_moroso' => $client->esMoroso(),
                        'status_name' => $client->is_active ? 'Activo' : 'Inactivo',
                    ];
                }),

            // 2. Almacenes: Para saber de dónde sale el hielo
            'warehouses' => Warehouse::select('id', 'name', 'type')
                ->get(),

            // 3. Productos con Stock: Relacionados con sus almacenes
            // Traemos los productos que tienen existencia en al menos un lugar
            'products' => InventoryStock::with(['product' => function ($query) {
                $query->select('id', 'name', 'price');
            }])
                ->where('quantity', '>', 0)
                ->get()
                ->filter(fn ($stock) => $stock->product !== null) // Evita errores si un stock no tiene producto
                ->map(function ($stock) {
                    return [
                        'id' => $stock->product_id,
                        'name' => $stock->product->name,
                        'price' => $stock->product->price,
                        'warehouse_id' => $stock->warehouse_id,
                        'stock' => $stock->quantity,
                    ];
                })->values()->toArray(),

            // 4. Configuración de Documento (Para previsualizar el siguiente folio)
            'document_config' => DocumentType::where('code', 'FAC')
                ->select('id', 'prefix', 'current_number')
                ->first(),

            'payment_types' => Sale::getPaymentTypes(),

            // Cuenta contable para ventas al contado (Default: Caja)
            'default_cash_account' => AccountingAccount::where('code', '1.1.01')
                ->select('id', 'name', 'code')
                ->first(),

            'ncf_types' => \App\Models\Sales\Ncf\NcfType::whereHas('sequences', function ($q) {
                $q->where('status', \App\Models\Sales\Ncf\NcfSequence::STATUS_ACTIVE)
                    ->where('expiry_date', '>=', now())
                    ->whereColumn('current', '<', 'to');
            })
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name,
                        'code' => $type->code,
                        'is_electronic' => $type->is_electronic,
                    ];
                }),

            'tipo_pagos' => TipoPago::sortByPriority(
                TipoPago::activo()->select('id', 'nombre', 'slug', 'accounting_account_id')->get()
            ),
        ];
    }
}
