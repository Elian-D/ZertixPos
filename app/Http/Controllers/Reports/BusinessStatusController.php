<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Payable;
use App\Models\Accounting\Receivable;
use App\Services\Reports\BusinessMetrics;
use Illuminate\Support\Facades\DB;

/**
 * Estado del negocio (v1.5.0 REQ-6.2): ¿en qué está el negocio hoy? Sin período.
 *   Dinero (Cajas y Bancos) + mercancía (a costo) + me deben (CxC) − debo (CxP) = neto.
 * No incluye el efectivo de los turnos abiertos: entra a una cuenta con un depósito.
 */
class BusinessStatusController extends Controller
{
    const LIST_LIMIT = 8;

    public function __invoke(BusinessMetrics $metrics)
    {
        // Cada parte solo cuenta si su módulo está encendido (v1.5.1 REQ-3.3).
        $show = [
            'inventory' => module_enabled('inventory.tracking'),
            'receivables' => module_enabled('sales.receivables'),
            'payables' => module_enabled('sales.payables'),
        ];

        $accounts = $metrics->accounts();
        $inventory = $show['inventory'] ? $metrics->inventoryValue() : ['cost' => 0.0];

        $money = round((float) $accounts->sum('current_balance'), 2);
        $receivables = module_enabled('sales.receivables') ? $metrics->receivablesPending() : 0.0;
        $payables = module_enabled('sales.payables') ? $metrics->payablesPending() : 0.0;

        return view('reports.business', [
            'show' => $show,
            'accounts' => $accounts,
            'byWarehouse' => $show['inventory'] ? $metrics->inventoryValueByWarehouse() : collect(),
            'totals' => [
                'money' => $money,
                'inventory' => $inventory['cost'],
                'receivables' => $receivables,
                'payables' => $payables,
                'net' => round($money + $inventory['cost'] + $receivables - $payables, 2),
            ],
            // Quién me debe más y a quién le debo más.
            'topDebtors' => module_enabled('sales.receivables')
                ? Receivable::query()->join('clients', 'clients.id', '=', 'receivables.client_id')
                    ->whereIn('receivables.status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
                    ->groupBy('clients.id', 'clients.name')
                    ->selectRaw('clients.name, COUNT(*) as count, SUM(receivables.current_balance) as total,
                        SUM(CASE WHEN receivables.due_date < CURDATE() THEN receivables.current_balance ELSE 0 END) as overdue')
                    ->orderByDesc('total')->limit(self::LIST_LIMIT)->get()
                : collect(),
            'topCreditors' => module_enabled('sales.payables')
                ? Payable::query()->open()->leftJoin('vendors', 'vendors.id', '=', 'payables.vendor_id')
                    ->groupBy('vendors.id', 'vendors.business_name', 'vendors.trade_name')
                    ->selectRaw("COALESCE(vendors.trade_name, vendors.business_name, 'Sin proveedor') as name, COUNT(*) as count, SUM(payables.balance) as total,
                        SUM(CASE WHEN payables.due_date < CURDATE() THEN payables.balance ELSE 0 END) as overdue")
                    ->orderByDesc(DB::raw('SUM(payables.balance)'))->limit(self::LIST_LIMIT)->get()
                : collect(),
        ]);
    }
}
