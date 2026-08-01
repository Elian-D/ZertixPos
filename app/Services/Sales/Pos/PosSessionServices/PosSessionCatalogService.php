<?php

namespace App\Services\Sales\Pos\PosSessionServices;

use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\User;

class PosSessionCatalogService
{
    /**
     * Datos para los filtros de la tabla de histórico de sesiones.
     */
    public function getForFilters(): array
    {
        return [
            'terminals' => PosTerminal::select('id', 'name')
                ->orderBy('name')
                ->get(),

            'users' => User::select('id', 'name')
                ->has('posSessions') 
                ->orderBy('name')
                ->get(),

            'statuses' => PosSession::getStatuses(),

            'difference_reasons' => PosSession::getReasons(),
        ];
    }

    /**
     * Datos para el formulario de apertura/gestión.
     */
    public function getForForm(): array
    {
        // Cuentas para Entradas (TYPE_IN): Patrimonio(3) o Ingresos(4)
        $incomeAccounts = \App\Models\Accounting\AccountingAccount::where('is_selectable', true)
            ->where(fn($q) => $q->where('code', 'like', '3%')->orWhere('code', 'like', '4%'))
            ->get(['id', 'code', 'name']);

        // Cuentas para Salidas (TYPE_OUT): Pasivos(2) o Gastos(5.3)
        $expenseAccounts = \App\Models\Accounting\AccountingAccount::where('is_selectable', true)
            ->where(fn($q) => $q->where('code', 'like', '2%')->orWhere('code', 'like', '5.3%'))
            ->get(['id', 'code', 'name']);

        return [
            'available_terminals' => PosTerminal::where('is_active', true)
                ->whereDoesntHave('sessions', function ($query) {
                    $query->where('status', PosSession::STATUS_OPEN);
                })
                ->select('id', 'name', 'warehouse_id', 'requires_pin')
                ->get(),
            
            'income_accounts' => $incomeAccounts,
            'expense_accounts' => $expenseAccounts,
        ];
    }
}