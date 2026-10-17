<?php

namespace App\Services\Sales\SalesServices;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\DocumentType;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use Illuminate\Support\Facades\DB;

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

            // 2. Almacenes. Con Inventario apagado solo el de respaldo, y el formulario
            // no lo pide (v1.5.1 REQ-3.3).
            'warehouses' => $warehouses = module_enabled('inventory.tracking')
                ? Warehouse::where('is_active', true)->select('id', 'name', 'type')->get()
                : collect([Warehouse::fallback()]),

            // 3. Productos por almacén: los que tienen existencia ahí, más los servicios
            // (no tienen existencia). Con Inventario apagado, todos los productos activos.
            'products' => (function () use ($warehouses) {
                $tracking = module_enabled('inventory.tracking');

                $products = Product::where('is_active', true)
                    ->select('id', 'name', 'price', 'type')
                    ->with(['stocks' => fn ($q) => $q->where('quantity', '>', 0)->select('id', 'product_id', 'warehouse_id', 'quantity')])
                    ->get();

                // Precargado en una sola query (evita N+1 de Product::taxRate() por
                // producto): suma de tasas apiladas por producto (Fase 5, REQ-5.1).
                $taxRates = DB::table('product_taxes')
                    ->whereIn('product_id', $products->pluck('id'))
                    ->get()
                    ->groupBy('product_id')
                    ->map(fn ($rows) => $rows->sum(fn ($row) => config("impuestos.{$row->tax_key}.rate", 0)));

                $row = fn (Product $product, int $warehouseId, ?float $stock) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    // Suma de tasas apiladas — el carrito la snapshotea al agregar
                    // el producto, igual que el precio.
                    'tax_rate' => (float) ($taxRates->get($product->id) ?? 0),
                    'warehouse_id' => $warehouseId,
                    'stock' => $stock,
                ];

                return $products->flatMap(function (Product $product) use ($tracking, $warehouses, $row) {
                    if (! $tracking || $product->isService()) {
                        return $warehouses->map(fn ($warehouse) => $row($product, $warehouse->id, null));
                    }

                    return $product->stocks->map(fn ($stock) => $row($product, $stock->warehouse_id, (float) $stock->quantity));
                })->values()->toArray();
            })(),

            // 4. Configuración de Documento (Para previsualizar el siguiente folio)
            'document_config' => DocumentType::where('code', 'VTA')
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
                TipoPago::seleccionable()->select('id', 'nombre', 'slug', 'accounting_account_id')->get()
            ),
        ];
    }
}
