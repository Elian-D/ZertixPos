<?php

namespace App\Services\Reports;

use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use App\Models\Accounting\Receivable;
use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryWaste;
use App\Models\Products\Product;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\Sales\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cifras del negocio para el inicio y los reportes (v1.5.0 Fase 6). Un solo lugar para
 * cada cálculo, así el inicio y el reporte nunca dicen números distintos.
 *
 * Criterios:
 *  - Ventas: solo completadas. "Total vendido" lleva ITBIS (lo que pagó el cliente); el
 *    estado de resultados usa el neto sin ITBIS (el ITBIS no es ingreso) menos devoluciones.
 *  - Costo de ventas: kardex de ventas, anulaciones y devoluciones al costo congelado de
 *    cada movimiento (`inventory_movements.unit_cost`), así un mes pasado no cambia con
 *    una compra nueva.
 *  - Pérdidas de inventario: mermas aplicadas y diferencias de tomas físicas aplicadas a su
 *    costo congelado, más los ajustes manuales.
 *  - Gastos: los registrados (no anulados). Una compra no es gasto: cambia dinero por
 *    mercancía.
 */
class BusinessMetrics
{
    /** Tipos del kardex cuyo costo es costo de ventas (las devoluciones lo reducen). */
    const COST_OF_SALES_TYPES = [
        InventoryMovement::TYPE_SALE,
        InventoryMovement::TYPE_SALE_VOID,
        InventoryMovement::TYPE_RETURN,
    ];

    // ------------------------------------------------------------------ Ventas

    /** @return array{gross: float, net: float, tax: float, count: int, cash: float, credit: float, average: float} */
    public function sales(CarbonInterface $from, CarbonInterface $to): array
    {
        $row = $this->salesQuery($from, $to)->selectRaw('
            COALESCE(SUM(net_amount + tax_amount), 0) as gross,
            COALESCE(SUM(net_amount), 0) as net,
            COALESCE(SUM(tax_amount), 0) as tax,
            COUNT(*) as count,
            COALESCE(SUM(CASE WHEN payment_type = ? THEN net_amount + tax_amount ELSE 0 END), 0) as credit
        ', [Sale::PAYMENT_CREDIT])->first();

        $gross = round((float) $row->gross, 2);
        $count = (int) $row->count;

        return [
            'gross' => $gross,
            'net' => round((float) $row->net, 2),
            'tax' => round((float) $row->tax, 2),
            'count' => $count,
            'credit' => round((float) $row->credit, 2),
            'cash' => round($gross - (float) $row->credit, 2),
            'average' => $count ? round($gross / $count, 2) : 0.0,
        ];
    }

    /** Lo devuelto en el período, sin ITBIS (resta a las ventas netas). */
    public function returnsNet(CarbonInterface $from, CarbonInterface $to): float
    {
        return round((float) DB::table('return_items')
            ->join('returns', 'returns.id', '=', 'return_items.return_id')
            ->where('returns.status', SaleReturn::STATUS_COMPLETED)
            ->whereBetween('returns.created_at', [$from, $to])
            ->sum(DB::raw('return_items.quantity * return_items.unit_subtotal')), 2);
    }

    /**
     * Total vendido por día, con todos los días del rango (también los que no vendieron).
     *
     * @return Collection<int, array{date: Carbon, value: float}>
     */
    public function salesByDay(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $byDay = $this->salesQuery($from, $to)
            ->selectRaw('DATE(sale_date) as day, SUM(net_amount + tax_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect();
        for ($d = Carbon::parse($from)->startOfDay(); $d->lte($to); $d->addDay()) {
            $days->push(['date' => $d->copy(), 'value' => round((float) ($byDay[$d->toDateString()] ?? 0), 2)]);
        }

        return $days;
    }

    /** Cobrado por método de pago (de los pagos reales, así un pago dividido cuenta en cada método). */
    public function salesByMethod(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $paid = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->leftJoin('tipo_pagos', 'tipo_pagos.id', '=', 'sale_payments.tipo_pago_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('tipo_pagos.nombre')
            ->selectRaw("COALESCE(tipo_pagos.nombre, 'Otro') as label, COUNT(DISTINCT sales.id) as count, SUM(sale_payments.amount) as total")
            ->get();

        // Las ventas a crédito no tienen pago: su método es "Crédito".
        $credit = $this->salesQuery($from, $to)->where('payment_type', Sale::PAYMENT_CREDIT)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(net_amount + tax_amount), 0) as total')->first();

        if ((int) $credit->count > 0) {
            $paid->push((object) ['label' => 'Crédito', 'count' => (int) $credit->count, 'total' => (float) $credit->total]);
        }

        return $paid->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count, 'value' => round((float) $r->total, 2)])
            ->sortByDesc('value')->values();
    }

    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.id, products.name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal + sale_items.tax_amount) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /** Mejores clientes, sin Consumidor Final. */
    public function topClients(CarbonInterface $from, CarbonInterface $to, int $limit = 5): Collection
    {
        return $this->salesQuery($from, $to)
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->where('clients.tax_id', '!=', '00000000000')
            ->groupBy('clients.id', 'clients.name')
            ->selectRaw('clients.id, clients.name, COUNT(*) as count, SUM(sales.net_amount + sales.tax_amount) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    // ------------------------------------------------------------------ Resultados

    /** Costo de lo vendido al costo congelado de cada movimiento. */
    public function costOfSales(CarbonInterface $from, CarbonInterface $to): float
    {
        return round((float) InventoryMovement::query()
            ->whereIn('type', self::COST_OF_SALES_TYPES)
            ->whereBetween('created_at', [$from, $to])
            ->sum(DB::raw('-quantity * COALESCE(unit_cost, 0)')), 2);
    }

    /**
     * Pérdidas de inventario del período, por concepto. Positivo = pérdida.
     *
     * @return array{waste: float, count_shortage: float, count_surplus: float, adjustments: float, total: float, by_reason: Collection}
     */
    public function inventoryLosses(CarbonInterface $from, CarbonInterface $to): array
    {
        $waste = (float) InventoryWaste::where('status', InventoryWaste::STATUS_APPLIED)
            ->whereBetween('waste_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_value');

        $counts = DB::table('inventory_count_items')
            ->join('inventory_counts', 'inventory_counts.id', '=', 'inventory_count_items.inventory_count_id')
            ->where('inventory_counts.status', InventoryCount::STATUS_APPLIED)
            ->whereBetween('inventory_counts.applied_at', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN inventory_count_items.difference < 0 THEN -inventory_count_items.difference * COALESCE(inventory_count_items.unit_cost, 0) ELSE 0 END), 0) as shortage,
                COALESCE(SUM(CASE WHEN inventory_count_items.difference > 0 THEN inventory_count_items.difference * COALESCE(inventory_count_items.unit_cost, 0) ELSE 0 END), 0) as surplus
            ')->first();

        // Ajustes manuales: lo que salió menos lo que entró, al costo congelado.
        $adjustments = (float) InventoryMovement::whereIn('type', [InventoryMovement::TYPE_ADJUSTMENT_IN, InventoryMovement::TYPE_ADJUSTMENT_OUT])
            ->whereBetween('created_at', [$from, $to])
            ->sum(DB::raw('-quantity * COALESCE(unit_cost, 0)'));

        $byReason = DB::table('inventory_waste_items')
            ->join('inventory_wastes', 'inventory_wastes.id', '=', 'inventory_waste_items.inventory_waste_id')
            ->where('inventory_wastes.status', InventoryWaste::STATUS_APPLIED)
            ->whereBetween('inventory_wastes.waste_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('inventory_waste_items.reason')
            ->selectRaw('inventory_waste_items.reason, SUM(inventory_waste_items.total_value) as total')
            ->orderByDesc('total')
            ->pluck('total', 'reason');

        $shortage = round((float) $counts->shortage, 2);
        $surplus = round((float) $counts->surplus, 2);

        return [
            'waste' => round($waste, 2),
            'count_shortage' => $shortage,
            'count_surplus' => $surplus,
            'adjustments' => round($adjustments, 2),
            'total' => round($waste + $shortage - $surplus + $adjustments, 2),
            'by_reason' => $byReason,
        ];
    }

    /** Gastos registrados (no anulados), por categoría. */
    public function expensesByCategory(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Expense::where('status', Expense::STATUS_APPLIED)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category')
            ->selectRaw('category, COUNT(*) as count, SUM(amount) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'label' => Expense::getCategories()[$r->category] ?? $r->category,
                'count' => (int) $r->count,
                'value' => round((float) $r->total, 2),
            ]);
    }

    /**
     * Estado de resultados del período.
     *
     * @return array{sales: float, returns: float, net_sales: float, cost: float, gross_profit: float, losses: array, expenses: float, expenses_by_category: Collection, result: float, margin: ?float}
     */
    public function incomeStatement(CarbonInterface $from, CarbonInterface $to): array
    {
        $sales = $this->sales($from, $to)['net'];
        $returns = $this->returnsNet($from, $to);
        $netSales = round($sales - $returns, 2);
        $cost = $this->costOfSales($from, $to);
        $gross = round($netSales - $cost, 2);
        $losses = $this->inventoryLosses($from, $to);
        $expensesByCategory = $this->expensesByCategory($from, $to);
        $expenses = round($expensesByCategory->sum('value'), 2);
        $result = round($gross - $losses['total'] - $expenses, 2);

        return [
            'sales' => $sales,
            'returns' => $returns,
            'net_sales' => $netSales,
            'cost' => $cost,
            'gross_profit' => $gross,
            'losses' => $losses,
            'expenses' => $expenses,
            'expenses_by_category' => $expensesByCategory,
            'result' => $result,
            'margin' => $netSales > 0 ? round($result / $netSales * 100, 1) : null,
        ];
    }

    // ------------------------------------------------------------------ Estado del negocio

    /** Cuentas activas de Cajas y Bancos con su saldo. */
    public function accounts(): Collection
    {
        return FinancialAccount::active()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'bank_name', 'current_balance']);
    }

    /**
     * Valor de la mercancía a costo y a precio, por almacén (o de uno). Solo existencias
     * positivas de productos tipo Producto.
     *
     * @return Collection<int, object{warehouse_id: int, name: string, skus: int, units: float, cost: float, price: float}>
     */
    public function inventoryValueByWarehouse(?int $warehouseId = null): Collection
    {
        return $this->stockValueQuery($warehouseId)
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->selectRaw('warehouses.id as warehouse_id, warehouses.name, COUNT(DISTINCT inventory_stocks.product_id) as skus,
                SUM(inventory_stocks.quantity) as units, SUM(inventory_stocks.quantity * products.cost) as cost,
                SUM(inventory_stocks.quantity * products.price) as price')
            ->orderByDesc('cost')
            ->get();
    }

    /**
     * Valor a costo por categoría principal, con sus subcategorías en `children`
     * (v1.5.1 REQ-2.4). Cada fila: name, skus, cost.
     */
    public function inventoryValueByCategory(?int $warehouseId = null): Collection
    {
        $rows = $this->stockValueQuery($warehouseId)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories as parents', 'parents.id', '=', 'categories.parent_id')
            ->groupBy('categories.id', 'categories.name', 'parents.name')
            ->selectRaw("COALESCE(parents.name, categories.name, 'Sin categoría') as parent, COALESCE(categories.name, 'Sin categoría') as name,
                COUNT(DISTINCT inventory_stocks.product_id) as skus, SUM(inventory_stocks.quantity * products.cost) as cost")
            ->get();

        return $rows->groupBy('parent')
            ->map(fn (Collection $children, string $parent) => (object) [
                'name' => $parent,
                'skus' => (int) $children->sum('skus'),
                'cost' => (float) $children->sum('cost'),
                'children' => $children->sortByDesc('cost')->values(),
            ])
            ->sortByDesc('cost')
            ->values();
    }

    /** @return array{cost: float, price: float, units: float, skus: int} */
    public function inventoryValue(?int $warehouseId = null): array
    {
        $row = $this->stockValueQuery($warehouseId)->selectRaw('
            COALESCE(SUM(inventory_stocks.quantity * products.cost), 0) as cost,
            COALESCE(SUM(inventory_stocks.quantity * products.price), 0) as price,
            COALESCE(SUM(inventory_stocks.quantity), 0) as units,
            COUNT(DISTINCT inventory_stocks.product_id) as skus
        ')->first();

        return [
            'cost' => round((float) $row->cost, 2),
            'price' => round((float) $row->price, 2),
            'units' => (float) $row->units,
            'skus' => (int) $row->skus,
        ];
    }

    public function receivablesPending(): float
    {
        return round((float) Receivable::whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])->sum('current_balance'), 2);
    }

    public function payablesPending(): float
    {
        return round((float) Payable::open()->sum('balance'), 2);
    }

    // ------------------------------------------------------------------ Inventario

    /** Existencias en o bajo el mínimo (las agotadas primero). */
    public function lowStock(?int $warehouseId = null, ?int $limit = 10): Collection
    {
        return InventoryStock::query()
            ->with(['product:id,name,sku', 'warehouse:id,name'])
            ->whereHas('product', fn ($q) => $q->where('type', Product::TYPE_PRODUCT)->where('is_active', true))
            ->when($warehouseId, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->where('min_stock', '>', 0)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity')
            ->when($limit, fn ($q, $n) => $q->limit($n))
            ->get();
    }

    public function lowStockCount(?int $warehouseId = null): int
    {
        return InventoryStock::query()
            ->whereHas('product', fn ($q) => $q->where('type', Product::TYPE_PRODUCT)->where('is_active', true))
            ->when($warehouseId, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->where('min_stock', '>', 0)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->count();
    }

    /** Existencias sin ningún movimiento en los últimos $days días (posible stock muerto). */
    public function deadStock(int $days = 60, ?int $warehouseId = null, int $limit = 15): Collection
    {
        $since = now()->subDays($days);

        return $this->stockValueQuery($warehouseId)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('inventory_movements')
                ->whereColumn('inventory_movements.product_id', 'inventory_stocks.product_id')
                ->whereColumn('inventory_movements.warehouse_id', 'inventory_stocks.warehouse_id')
                ->where('inventory_movements.created_at', '>=', $since))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.id, products.name, SUM(inventory_stocks.quantity) as units, SUM(inventory_stocks.quantity * products.cost) as cost')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get();
    }

    // ------------------------------------------------------------------ Internos

    private function salesQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return Sale::query()->where('sales.status', Sale::STATUS_COMPLETED)->whereBetween('sales.sale_date', [$from, $to]);
    }

    private function stockValueQuery(?int $warehouseId)
    {
        return DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->where('products.type', Product::TYPE_PRODUCT)
            ->whereNull('products.deleted_at')
            ->where('inventory_stocks.quantity', '>', 0)
            ->when($warehouseId, fn ($q, $id) => $q->where('inventory_stocks.warehouse_id', $id));
    }
}
