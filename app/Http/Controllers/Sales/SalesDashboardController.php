<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ClientCollection;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $range = $request->get('range', '30days');

        // Seteamos horas exactas para no perder ventas del último día
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
            case 'custom':
                $startDay = \Carbon\Carbon::parse($request->get('start_date'))->startOfDay();
                $endDay = \Carbon\Carbon::parse($request->get('end_date'))->endOfDay();
                break;
        }

        // 1. KPIs Globales (Sin Joins para asegurar que cuente TODO, incluido POS)
        // SUM(net_amount + tax_amount) — equivalente SQL de grand_total (no es una
        // columna real, es un accessor de Sale) — no SUM(total_amount), que además de
        // ser bruto sin impuesto, no tiene ni el mismo significado entre ventas
        // normales y ventas convertidas desde cotización antes de esta corrección
        // (Fase 5, auditoría post-REQ-5.12, ver docs/features/v1.2.0.md).
        $salesStats = Sale::whereBetween('sale_date', [$startDay, $endDay])
            ->where('status', 'completed')
            ->selectRaw('
                SUM(net_amount + tax_amount) as total_revenue,
                COUNT(*) as total_count,
                SUM(CASE WHEN payment_type = "credit" THEN net_amount + tax_amount ELSE 0 END) as credit_total,
                SUM(CASE WHEN payment_type = "cash" THEN net_amount + tax_amount ELSE 0 END) as cash_total
            ')->first();

        // 2. Efectividad de Cobro
        $totalCollected = ClientCollection::whereBetween('payment_date', [$startDay, $endDay])->sum('amount');

        // 3. Top 5 Clientes (Excluyendo Consumidor Final del Ranking)
        $topClients = Sale::whereBetween('sale_date', [$startDay, $endDay])
            ->where('sales.status', 'completed')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->where('clients.name', 'NOT LIKE', '%Consumidor Final%')
            ->select('clients.name', DB::raw('SUM(net_amount + tax_amount) as total'))
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 4. Métodos de Pago (Usamos LEFT JOIN para no perder ventas si falla la relación)
        $paymentMethods = Sale::whereBetween('sale_date', [$startDay, $endDay])
            ->where('sales.status', 'completed')
            ->leftJoin('tipo_pagos', 'sales.tipo_pago_id', '=', 'tipo_pagos.id')
            ->select(
                DB::raw('COALESCE(tipo_pagos.nombre, "Otro/POS") as nombre'),
                DB::raw('SUM(net_amount + tax_amount) as total')
            )
            ->groupBy('tipo_pagos.nombre')
            ->get();

        // 5. Línea de Tiempo
        $timeline = $this->getSalesTimeline($startDay, $endDay);

        // 6. Top Productos
        $topProducts = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$startDay, $endDay])
            ->where('sales.status', 'completed')
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->take(5)
            ->get();

        return view('sales.dashboard', [
            'stats' => [
                'total_revenue' => $salesStats->total_revenue ?? 0,
                'total_count' => $salesStats->total_count ?? 0,
                'credit_total' => $salesStats->credit_total ?? 0,
                'cash_total' => $salesStats->cash_total ?? 0,
                'collected' => $totalCollected,
                'avg_ticket' => ($salesStats->total_count ?? 0) > 0 ? ($salesStats->total_revenue / $salesStats->total_count) : 0,
            ],
            'topProducts' => $topProducts,
            'topClients' => $topClients,
            'charts' => [
                'timeline' => [
                    'labels' => $timeline->pluck('date'),
                    'values' => $timeline->pluck('total'),
                ],
                'methods' => [
                    'labels' => $paymentMethods->pluck('nombre'),
                    'values' => $paymentMethods->pluck('total'),
                ],
            ],
            'recentSales' => Sale::with(['client', 'tipoPago'])->latest()->take(8)->get(),
            'filters' => [
                'start' => $startDay->format('Y-m-d'),
                'end' => $endDay->format('Y-m-d'),
                'current_range' => $range,
            ],
        ]);
    }

    private function getSalesTimeline($start, $end)
    {
        return Sale::whereBetween('sale_date', [$start, $end])
            ->where('status', 'completed')
            ->select(
                DB::raw("DATE_FORMAT(sale_date, '%d %b') as date"),
                DB::raw('SUM(net_amount + tax_amount) as total'),
                DB::raw('MIN(sale_date) as sort_date')
            )
            ->groupBy('date') // Agrupamos solo por el string formateado
            ->orderBy('sort_date')
            ->get();
    }
}
