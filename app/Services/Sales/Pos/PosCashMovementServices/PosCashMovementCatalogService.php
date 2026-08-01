<?php

namespace App\Services\Sales\Pos\PosCashMovementServices;

use App\Models\User;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosCashMovement;
use App\Models\Accounting\AccountingAccount;

class PosCashMovementCatalogService
{
    public function getForFilters(): array
    {
        return [
            'users' => User::query()
                ->whereHas('posCashMovements')
                ->orderBy('name')
                ->get(['id', 'name']),

            // Solo sesiones abiertas para el selector de "Sesión de Caja Activa"
            'sessions' => PosSession::with(['terminal', 'user'])
                ->where('status', PosSession::STATUS_OPEN)
                ->latest()
                ->get(),

            'types' => [
                PosCashMovement::TYPE_IN  => 'Entrada de Efectivo',
                PosCashMovement::TYPE_OUT => 'Salida de Efectivo',
            ],
        ];
    }

    public function getForForm(): array
    {
        // Reutilizamos los filtros base (trae las sesiones abiertas)
        $catalog = $this->getForFilters();

        // Cuentas para Entradas (TYPE_IN): Patrimonio (3) o Ingresos (4)
        $catalog['income_accounts'] = AccountingAccount::where('is_selectable', true)
            ->where(fn($q) => $q->where('code', 'like', '3%')->orWhere('code', 'like', '4%'))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Cuentas para Salidas (TYPE_OUT): Pasivos (2) o Gastos (5.3)
        $catalog['expense_accounts'] = AccountingAccount::where('is_selectable', true)
            ->where(fn($q) => $q->where('code', 'like', '2%')->orWhere('code', 'like', '5.3%'))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return $catalog;
    }
}