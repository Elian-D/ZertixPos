<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\JournalItem;
use App\Models\Accounting\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingDashboardController extends Controller
{
    
    public function __invoke(Request $request)
    {
        // ===== SISTEMA DE FILTROS (IGUAL AL DASHBOARD DE VENTAS) =====
        $range = $request->get('range', '30days');
        
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
                $startDay = \Carbon\Carbon::parse($request->get('start_date'))->startOfDay();
                $endDay = \Carbon\Carbon::parse($request->get('end_date'))->endOfDay();
                break;
        }

        // 2. BALANCES DE CUENTAS (CORRECCIÓN TERMINALES E INVENTARIO)
        // 1.1.01 ahora incluye recursivamente todas las sub-cajas de terminales.
        $cashBalance = $this->getAccountBalanceByCode('1.1.01', $startDay, $endDay);
        $cxcBalance = $this->getAccountBalanceByCode('1.1.02', $startDay, $endDay);
        
        // 1.1.03 ahora es PURA (solo inventario físico) porque las terminales ya no cuelgan de aquí.
        $inventoryValue = $this->getAccountBalanceByCode('1.1.03', $startDay, $endDay);
        $cxpBalance = abs($this->getAccountBalanceByCode('2.1', $startDay, $endDay));

        // 3. CÁLCULOS DE RENDIMIENTO
        $income = abs($this->getAccountBalanceByCode('4.1', $startDay, $endDay));
        $costOfSales = $this->getAccountBalanceByCode('5.1', $startDay, $endDay);
        $grossProfit = $income - $costOfSales;

        // 4. LIQUIDEZ (CORRECCIÓN DE RATIO ALTO)
        $currentAssets = $cashBalance + $cxcBalance + $inventoryValue;
        
        // Si no hay deudas, el ratio es 100% positivo (representado como 2.5 para no romper gráficos)
        $liquidityRatio = $cxpBalance > 0 ? ($currentAssets / $cxpBalance) : ($currentAssets > 0 ? 3.0 : 0);

        // 5. FLUJO DE CAJA (Dinero Real saliendo de la 1.1.01)
        $totalInflows = $this->getTotalInflows($startDay, $endDay);
        $totalOutflows = $this->getTotalOutflows($startDay, $endDay);
        $cashFlow = $totalInflows - $totalOutflows;

        // 6. ALERTAS (Lógica suavizada)
        $alerts = $this->generateFinancialAlerts($liquidityRatio, $cashFlow, $cxcBalance, $cxpBalance);

        // ===== DATOS PARA GRÁFICOS =====
        $monthlyPerformance = $this->getMonthlyPerformance($startDay, $endDay);
        $expenseDistribution = $this->getExpenseDistribution($startDay, $endDay);
        $cashflowTimeline = $this->getCashflowTimeline($startDay, $endDay);

        return view('accounting.dashboard', [
            'stats' => [
                'cash_balance'    => $cashBalance,
                'cxc_balance'     => $cxcBalance,
                'cxp_balance'     => $cxpBalance,
                'inventory_value' => $inventoryValue,
                'gross_profit'    => $grossProfit,
                'profit_margin'   => $income > 0 ? ($grossProfit / $income) * 100 : 0,
                'liquidity_ratio' => $liquidityRatio,
                'cash_flow'       => $cashFlow,
            ],
            'charts' => [
                'performance' => [
                    'labels' => $monthlyPerformance->pluck('month'),
                    'income' => $monthlyPerformance->pluck('income'),
                    'costs'  => $monthlyPerformance->pluck('costs'),
                ],
                'composition' => [
                    'labels' => ['Disponible', 'Por Cobrar', 'Inventario'],
                    'values' => [max(0, $cashBalance), max(0, $cxcBalance), max(0, $inventoryValue)]
                ],
                'expenses' => [
                    'labels' => $expenseDistribution->pluck('name'),
                    'values' => $expenseDistribution->pluck('total'),
                ],
                'cashflow' => [ // NUEVO
                    'labels' => $cashflowTimeline->pluck('date'),
                    'inflows' => $cashflowTimeline->pluck('inflows'),
                    'outflows' => $cashflowTimeline->pluck('outflows'),
                ]
            ],
            'alerts' => $alerts, // NUEVO
            'recentEntries' => JournalEntry::with('creator')
                ->whereBetween('entry_date', [$startDay, $endDay])
                ->latest()
                ->take(8)
                ->get(),
            'filters' => [
                'start' => $startDay->format('Y-m-d'),
                'end' => $endDay->format('Y-m-d'),
                'current_range' => $range
            ]
        ]);
    }

    // ===== SISTEMA DE ALERTAS FINANCIERAS ACTUALIZADO =====
    private function generateFinancialAlerts($liquidityRatio, $cashFlow, $cxcBalance, $cxpBalance)
    {
        $alerts = [];

        // 1. Liquidez (Suavizada para evitar falsas alarmas en el inicio)
        if ($liquidityRatio < 1.0 && $cxpBalance > 0) {
            $alerts[] = [
                'type' => 'danger',
                'color' => 'red',
                'title' => 'Liquidez Crítica',
                'message' => 'Sus obligaciones a corto plazo superan sus activos disponibles.'
            ];
        } elseif ($liquidityRatio >= 3.0 && $cxpBalance == 0) {
            // Caso de inicio de operaciones o sin deudas
            $alerts[] = [
                'type' => 'success',
                'color' => 'blue',
                'title' => 'Estructura Inicial Limpia',
                'message' => 'No presenta deudas corrientes registradas. Su capital está disponible en activos.'
            ];
        }

        // 2. Flujo de Caja
        if ($cashFlow < 0) {
            $alerts[] = [
                'type' => 'warning',
                'color' => 'orange',
                'title' => 'Déficit de Efectivo',
                'message' => 'En este período ha salido más dinero del que ha ingresado a caja.'
            ];
        }

        // 3. Riesgo de Cartera (CxC vs CxP)
        // Si las cuentas por cobrar son 2 veces mayores a las de pagar, hay un problema de gestión de cobro.
        if ($cxcBalance > ($cxpBalance * 2) && $cxcBalance > 500) {
            $alerts[] = [
                'type' => 'warning',
                'color' => 'yellow',
                'title' => 'Cartera Sobre-extendida',
                'message' => 'Sus cuentas por cobrar triplican sus deudas. Hay mucho capital de trabajo atrapado en clientes que no han pagado.'
            ];
        }

        // 4. Alerta de "Dinero Ocioso" (Oportunidad de Inversión)
        // Si tienes mucha liquidez y flujo positivo, podrías estar perdiendo rendimiento.
        if ($liquidityRatio > 3.0 && $cashFlow > 1000) {
            $alerts[] = [
                'type' => 'success',
                'color' => 'indigo',
                'title' => 'Exceso de Liquidez',
                'message' => 'Mantiene un ratio de liquidez de ' . number_format($liquidityRatio, 1) . '. Considere invertir el excedente en inventario crítico o reducir pasivos con costo financiero.'
            ];
        }

        // 5. Salud Financiera Óptima
        if ($liquidityRatio >= 1.2 && $liquidityRatio <= 2.5 && $cashFlow >= 0) {
            $alerts[] = [
                'type' => 'success',
                'color' => 'green',
                'title' => 'Operación Equilibrada',
                'message' => 'La empresa presenta un balance saludable entre disponibilidad, deuda y flujo operativo.'
            ];
        }

        return $alerts;
    }

    // ===== NUEVO: FLUJO DE CAJA EN TIMELINE =====
    private function getCashflowTimeline($start, $end)
    {
        return JournalItem::select(
                DB::raw("DATE_FORMAT(journal_entries.entry_date, '%d %b') as date"),
                // Dinero que entró a la 1.1.01
                DB::raw("SUM(CASE WHEN accounting_accounts.code LIKE '1.1.01%' THEN debit ELSE 0 END) as inflows"),
                // Dinero que salió de la 1.1.01
                DB::raw("SUM(CASE WHEN accounting_accounts.code LIKE '1.1.01%' THEN credit ELSE 0 END) as outflows"),
                DB::raw('MIN(journal_entries.entry_date) as sort_date')
            )
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounting_accounts', 'journal_items.accounting_account_id', '=', 'accounting_accounts.id')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->where('journal_entries.status', 'posted')
            ->groupBy('date')
            ->orderBy('sort_date')
            ->get();
    }

    // Cambia este método para medir dinero REAL entrando a caja
    private function getTotalInflows($start, $end)
    {
        return JournalItem::whereHas('entry', function($q) use ($start, $end) {
                $q->whereBetween('entry_date', [$start, $end])
                ->where('status', 'posted');
            })
            ->whereHas('account', function($q) {
                $q->where('code', 'like', '1.1.01%'); // Solo cuenta de Caja/Bancos
            })
            ->where('debit', '>', 0) // El débito en un activo es una entrada de dinero
            ->sum('debit') ?? 0;
    }

    // Cambia este método para medir dinero REAL saliendo de caja
    private function getTotalOutflows($start, $end)
    {
        return JournalItem::whereHas('entry', function($q) use ($start, $end) {
                $q->whereBetween('entry_date', [$start, $end])
                ->where('status', 'posted');
            })
            ->whereHas('account', function($q) {
                $q->where('code', 'like', '1.1.01%'); // Solo cuenta de Caja/Bancos
            })
            ->where('credit', '>', 0) // El crédito en un activo es una salida de dinero
            ->sum('credit') ?? 0;
    }

    // ===== DISTRIBUCIÓN DE GASTOS (ACTUALIZADO CON FILTRO) =====
    private function getExpenseDistribution($start, $end)
    {
        return JournalItem::select(
                'accounting_accounts.name',
                DB::raw('SUM(journal_items.debit - journal_items.credit) as total')
            )
            ->join('accounting_accounts', 'journal_items.accounting_account_id', '=', 'accounting_accounts.id')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->where('journal_entries.status', 'posted')
            ->where('accounting_accounts.code', 'like', '5%') // Todos los gastos
            ->where('accounting_accounts.code', 'not like', '5.1%') // Excluir costo de ventas
            ->groupBy('accounting_accounts.id', 'accounting_accounts.name')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->take(5) // Top 5 gastos
            ->get();
    }

    // ===== BALANCE DE CUENTA CON FILTRO DE FECHA =====
    private function getAccountBalanceByCode(string $code, $start, $end)
    {
        // Cuentas de Balance: 1 (Activo), 2 (Pasivo), 3 (Patrimonio)
        $isBalanceAccount = in_array(substr($code, 0, 1), ['1', '2', '3']);

        return JournalItem::whereHas('account', function($q) use ($code) {
                $q->where('code', 'like', $code . '%');
            })
            ->whereHas('entry', function($q) use ($start, $end, $isBalanceAccount) {
                $q->where('status', 'posted');
                
                if ($isBalanceAccount) {
                    // IMPORTANTE: Para Activos/Pasivos el "Filtro de Fecha" es un "A la fecha de corte"
                    // Ignoramos el $start porque el saldo es histórico.
                    $q->where('entry_date', '<=', $end);
                } else {
                    // Para Ingresos/Gastos: Solo lo ocurrido en el rango (P&L)
                    $q->whereBetween('entry_date', [$start, $end]);
                }
            })
            ->selectRaw('SUM(debit - credit) as balance')
            ->value('balance') ?? 0;
    }

    // ===== RENDIMIENTO MENSUAL (ACTUALIZADO CON FILTRO) =====
    private function getMonthlyPerformance($start, $end)
    {
        return JournalItem::select(
                DB::raw("DATE_FORMAT(journal_entries.entry_date, '%b') as month"),
                DB::raw("SUM(CASE WHEN accounting_accounts.code LIKE '4%' THEN (credit - debit) ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN accounting_accounts.code LIKE '5%' THEN (debit - credit) ELSE 0 END) as costs")
            )
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounting_accounts', 'journal_items.accounting_account_id', '=', 'accounting_accounts.id')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->where('journal_entries.status', 'posted')
            ->groupBy('month', DB::raw("MONTH(journal_entries.entry_date)"))
            ->orderBy(DB::raw("MONTH(journal_entries.entry_date)"))
            ->get();
    }
}