<?php

namespace App\Http\Controllers;

use App\Models\Accounting\Payable;
use App\Models\Accounting\Receivable;
use App\Models\Sales\Pos\PosSession;
use App\Services\Reports\BusinessMetrics;

/**
 * Inicio (v1.5.0 REQ-6.5): el centro operativo del negocio, de arriba abajo:
 *   1. Onboarding (mientras falten pasos).
 *   2. Hoy: ventas, transacciones, turnos abiertos y dinero en Cajas y Bancos.
 *   3. El mes: ventas contra el mes anterior, ganancia bruta, por cobrar y por pagar.
 *   4. Ventas por día del mes y stock bajo.
 *   5. Lo que vence: CxC y CxP.
 * Cada bloque solo se calcula (y se ve) si el usuario tiene el permiso y el módulo está
 * encendido. Las cifras salen de BusinessMetrics, las mismas de los reportes.
 */
class DashboardController extends Controller
{
    const LIST_LIMIT = 5;

    /** CxP que vencen en estos días (más las vencidas). */
    const PAYABLES_WINDOW_DAYS = 7;

    public function __invoke(BusinessMetrics $metrics)
    {
        $user = auth()->user();
        $can = [
            'sales' => $user->can('sales.view'),
            'profit' => $user->can('accounting.dashboard'),
            'sessions' => $user->can('pos_sessions.history') || $user->can('pos_sessions.manage'),
            'accounts' => $user->can('financial_accounts.view'),
            'receivables' => module_enabled('sales.receivables') && $user->can('receivables.view'),
            'payables' => module_enabled('sales.payables') && $user->can('payables.view'),
            'stock' => module_enabled('inventory.tracking') && $user->can('inventory_stocks.view'),
            'order' => module_enabled('purchases') && $user->can('purchase_orders.create'),
        ];

        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        // El mes anterior hasta el mismo día: comparar el mes completo contra la mitad de este no dice nada.
        $prevStart = $monthStart->copy()->subMonthNoOverflow();
        $prevEnd = $prevStart->copy()->addDays($today->day - 1)->min($prevStart->copy()->endOfMonth())->endOfDay();

        $data = ['can' => $can];

        if ($can['sales']) {
            $data['todaySales'] = $metrics->sales($today->copy()->startOfDay(), $today->copy()->endOfDay());
            $data['monthSales'] = $metrics->sales($monthStart, $today->copy()->endOfDay());
            $data['prevSales'] = $metrics->sales($prevStart, $prevEnd);
            $data['salesByDay'] = $metrics->salesByDay($monthStart, $today->copy()->endOfDay());
        }

        if ($can['profit']) {
            $net = $data['monthSales']['net'] ?? $metrics->sales($monthStart, $today->copy()->endOfDay())['net'];
            $data['grossProfit'] = round($net - $metrics->returnsNet($monthStart, $today) - $metrics->costOfSales($monthStart, $today->copy()->endOfDay()), 2);
        }

        if ($can['sessions']) {
            $data['openSessions'] = PosSession::open()->with(['terminal:id,name', 'user:id,name'])->orderBy('opened_at')->get();
        }

        if ($can['accounts']) {
            $data['accounts'] = $metrics->accounts();
        }

        if ($can['receivables']) {
            $data['receivablesTotal'] = $metrics->receivablesPending();
            $data['receivables'] = Receivable::with('client:id,name')
                ->whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
                ->orderBy('due_date')->limit(self::LIST_LIMIT)->get();
        }

        if ($can['payables']) {
            $data['payablesTotal'] = $metrics->payablesPending();
            $data['payables'] = Payable::open()->with('vendor:id,business_name,trade_name')
                ->whereDate('due_date', '<=', $today->copy()->addDays(self::PAYABLES_WINDOW_DAYS))
                ->orderBy('due_date')->limit(self::LIST_LIMIT)->get();
        }

        if ($can['stock']) {
            $data['lowStock'] = $metrics->lowStock(limit: 8);
            $data['lowStockCount'] = $metrics->lowStockCount();
        }

        return view('dashboard', $data);
    }
}
