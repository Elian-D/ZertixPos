<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Filtro de fecha
        $range = $request->get('range', '7days');
        
        $filters = match($range) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'current_range' => 'today'
            ],
            '30days' => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
                'current_range' => '30days'
            ],
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfDay(),
                'current_range' => 'this_month'
            ],
            'custom' => [
                'start' => $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay() : now()->subDays(7)->startOfDay(),
                'end' => $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay(),
                'current_range' => 'custom'
            ],
            default => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now()->endOfDay(),
                'current_range' => '7days'
            ],
        };

        $startDate = $filters['start'];
        $endDate = $filters['end'];

        // 1. Datos para gráfico de Flujo (agrupados por fecha). Entradas y salidas se
        // cuentan por el signo de la cantidad, no por el tipo: así entran todos los
        // tipos (venta, compra, merma, transferencia...) y el histórico (v1.5.0 REQ-1.1).
        $history = InventoryMovement::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as inputs'),
                DB::raw('SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as outputs')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Generar array de fechas completo para evitar huecos
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()
        );

        $dates = [];
        $inputs = [];
        $outputs = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dates[] = $date->format('d M');
            
            $dayData = $history->firstWhere('date', $dateStr);
            $inputs[] = $dayData ? (float)$dayData->inputs : 0;
            $outputs[] = $dayData ? (float)$dayData->outputs : 0;
        }

        // 2. Distribución por Almacén
        $warehouseDist = Warehouse::withSum('stocks as total_qty', 'quantity')->get();

        // 3. Top Productos (Rotación)
        $topProducts = InventoryMovement::select('product_id', DB::raw('SUM(ABS(quantity)) as total_moved'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_moved')
            ->take(5)
            ->get();

        $totalInputs = InventoryMovement::where('quantity', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        $totalOutputs = InventoryMovement::where('quantity', '<', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('ABS(quantity)'));

        $lowStockCount = InventoryStock::whereColumn('quantity', '<=', 'min_stock')->count();

        return view('inventory.dashboard', [
            'filters' => [
                'current_range' => $filters['current_range'],
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'stats' => [
                'total_products' => Product::count(),
                'total_stock' => InventoryStock::sum('quantity'),
                'active_warehouses' => Warehouse::count(),
                'low_stock' => $lowStockCount,
                'total_inputs' => $totalInputs,
                'total_outputs' => $totalOutputs,
            ],
            'charts' => [
                'history' => [
                    'labels' => $dates,
                    'inputs' => $inputs,
                    'outputs' => $outputs,
                ],
                'distribution' => [
                    'labels' => $warehouseDist->pluck('name'),
                    'values' => $warehouseDist->pluck('total_qty')->map(fn($v) => (float)($v ?? 0)),
                ],
                'top_products' => [
                    'labels' => $topProducts->map(fn($tp) => $tp->product->name ?? 'N/A'),
                    'values' => $topProducts->pluck('total_moved')->map(fn($v) => (float)$v),
                ]
            ],
            'recentMovements' => InventoryMovement::with(['product', 'warehouse'])
                ->latest()
                ->take(10)
                ->get(),
            'lowStockProducts' => InventoryStock::with(['product', 'warehouse'])
                ->whereColumn('quantity', '<=', 'min_stock')
                ->orderBy('quantity', 'asc')
                ->take(5)
                ->get(),
        ]);
    }
}