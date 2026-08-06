<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use App\Models\Inventory\InventoryMovement;
use App\Models\Sales\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Ingresos y Gastos — el reporte base de la Fase 3 (REQ-03.7).
 *
 * A propósito NO toca JournalEntry/JournalItem en ningún punto: se arma solo con
 * Sale (ingresos), InventoryMovement + Product.cost (costo de ventas) y Payment/
 * Receivable (cobros y CxC pendiente) — las mismas fuentes que ya funcionan sin
 * `accounting.advanced`. Es el sustituto real de la partida doble para esta
 * versión, no un complemento: si accounting.advanced está apagado, esta es la
 * única vista financiera de la instalación.
 */
class FinancialOverviewController extends Controller
{
    public function __invoke(Request $request)
    {
        $range = $request->get('range', '7days');
        [$startDay, $endDay] = $this->resolveRange($range, $request);

        $salesStats = Sale::whereBetween('sale_date', [$startDay, $endDay])
            ->where('status', Sale::STATUS_COMPLETED)
            ->selectRaw('
                SUM(total_amount) as revenue,
                COUNT(*) as count,
                SUM(CASE WHEN payment_type = "credit" THEN total_amount ELSE 0 END) as credit_total,
                SUM(CASE WHEN payment_type = "cash" THEN total_amount ELSE 0 END) as cash_total
            ')
            ->first();

        $revenue = (float) ($salesStats->revenue ?? 0);
        $costOfSales = $this->getCostOfSales($startDay, $endDay);
        $grossProfit = $revenue - $costOfSales;
        $margin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        $collected = (float) Payment::whereBetween('payment_date', [$startDay, $endDay])
            ->where('status', Payment::STATUS_ACTIVE)
            ->sum('amount');

        $receivablePending = (float) Receivable::whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
            ->sum('current_balance');

        $timeline = $this->getRevenueCostTimeline($startDay, $endDay);

        $topReceivables = Receivable::with('client')
            ->whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
            ->orderBy('due_date')
            ->take(8)
            ->get();

        return view('accounting.overview', [
            'stats' => [
                'revenue' => $revenue,
                'sales_count' => (int) ($salesStats->count ?? 0),
                'cost_of_sales' => $costOfSales,
                'gross_profit' => $grossProfit,
                'margin' => $margin,
                'collected' => $collected,
                'receivable_pending' => $receivablePending,
                'cash_total' => (float) ($salesStats->cash_total ?? 0),
                'credit_total' => (float) ($salesStats->credit_total ?? 0),
            ],
            'charts' => [
                'timeline' => [
                    'labels' => $timeline->pluck('date'),
                    'revenue' => $timeline->pluck('revenue'),
                    'cost' => $timeline->pluck('cost'),
                ],
                'composition' => [
                    'labels' => ['Contado', 'Crédito'],
                    'values' => [$salesStats->cash_total ?? 0, $salesStats->credit_total ?? 0],
                ],
            ],
            'topReceivables' => $topReceivables,
            'filters' => [
                'start' => $startDay->format('Y-m-d'),
                'end' => $endDay->format('Y-m-d'),
                'current_range' => $range,
            ],
        ]);
    }

    private function resolveRange(string $range, Request $request): array
    {
        $startDay = now()->subDays(30)->startOfDay();
        $endDay = now()->endOfDay();

        switch ($range) {
            case 'today':
                $startDay = now()->startOfDay();
                break;
            case '7days':
                $startDay = now()->subDays(7)->startOfDay();
                break;
            case 'this_month':
                $startDay = now()->startOfMonth()->startOfDay();
                break;
            case 'this_year':
                $startDay = now()->startOfYear()->startOfDay();
                break;
            case 'custom':
                $startDay = Carbon::parse($request->get('start_date'))->startOfDay();
                $endDay = Carbon::parse($request->get('end_date'))->endOfDay();
                break;
        }

        return [$startDay, $endDay];
    }

    /**
     * Costo de lo vendido en el periodo: salidas físicas de inventario (kardex),
     * valoradas al costo ACTUAL del producto — igual criterio que ya usa
     * InventoryMovementService, sin depender de que exista un asiento contable.
     */
    private function getCostOfSales(Carbon $start, Carbon $end): float
    {
        return (float) InventoryMovement::query()
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->where('inventory_movements.type', InventoryMovement::TYPE_OUTPUT)
            ->whereBetween('inventory_movements.created_at', [$start, $end])
            ->whereNull('inventory_movements.deleted_at')
            ->selectRaw('SUM(ABS(inventory_movements.quantity) * products.cost) as total')
            ->value('total') ?? 0;
    }

    private function getRevenueCostTimeline(Carbon $start, Carbon $end)
    {
        $revenueByDay = Sale::whereBetween('sale_date', [$start, $end])
            ->where('status', Sale::STATUS_COMPLETED)
            ->selectRaw("DATE(sale_date) as day, DATE_FORMAT(sale_date, '%d %b') as label, SUM(total_amount) as revenue")
            ->groupBy('day', 'label')
            ->pluck('revenue', 'day');

        $costByDay = InventoryMovement::query()
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->where('inventory_movements.type', InventoryMovement::TYPE_OUTPUT)
            ->whereBetween('inventory_movements.created_at', [$start, $end])
            ->whereNull('inventory_movements.deleted_at')
            ->selectRaw('DATE(inventory_movements.created_at) as day, SUM(ABS(inventory_movements.quantity) * products.cost) as cost')
            ->groupBy('day')
            ->pluck('cost', 'day');

        $period = collect();
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $period->push([
                'date' => $cursor->format('d M'),
                'revenue' => (float) ($revenueByDay[$key] ?? 0),
                'cost' => (float) ($costByDay[$key] ?? 0),
            ]);
            $cursor->addDay();
        }

        return $period;
    }
}
