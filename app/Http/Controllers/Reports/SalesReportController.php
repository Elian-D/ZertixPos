<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sales\Sale;
use App\Services\Reports\BusinessMetrics;
use App\Support\Reports\ReportPeriod;
use Illuminate\Http\Request;

/** Reporte de Ventas (v1.5.0 Fase 6): qué se vendió en el período, cómo se pagó y a quién. */
class SalesReportController extends Controller
{
    public function __invoke(Request $request, BusinessMetrics $metrics)
    {
        $period = ReportPeriod::fromRequest($request);

        return view('reports.sales', [
            'period' => $period,
            'sales' => $metrics->sales($period->from, $period->to),
            'returns' => $metrics->returnsNet($period->from, $period->to),
            'byDay' => $metrics->salesByDay($period->from, $period->to),
            'byMethod' => $metrics->salesByMethod($period->from, $period->to),
            'topProducts' => $metrics->topProducts($period->from, $period->to),
            'topClients' => $metrics->topClients($period->from, $period->to),
            'recent' => Sale::with('client:id,name')
                ->where('status', Sale::STATUS_COMPLETED)
                ->whereBetween('sale_date', [$period->from, $period->to])
                ->latest('sale_date')->latest('id')->limit(8)
                ->get(['id', 'number', 'client_id', 'sale_date', 'payment_type', 'net_amount', 'tax_amount']),
        ]);
    }
}
