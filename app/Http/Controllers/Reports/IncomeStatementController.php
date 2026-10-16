<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryWasteItem;
use App\Services\Reports\BusinessMetrics;
use App\Support\Reports\ReportPeriod;
use Illuminate\Http\Request;

/**
 * Estado de resultados (v1.5.0 REQ-6.1, antes "Ingresos y Gastos" en Finanzas): ¿cómo me fue
 * en el período? Ventas netas − costo de lo vendido = ganancia bruta; − pérdidas de
 * inventario − gastos = resultado. Sin asientos contables (Decisión 4).
 */
class IncomeStatementController extends Controller
{
    public function __invoke(Request $request, BusinessMetrics $metrics)
    {
        $period = ReportPeriod::fromRequest($request);

        return view('reports.results', [
            'period' => $period,
            'statement' => $metrics->incomeStatement($period->from, $period->to),
            'reasons' => InventoryWasteItem::getReasons(),
        ]);
    }
}
