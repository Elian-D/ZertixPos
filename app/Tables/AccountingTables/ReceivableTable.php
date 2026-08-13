<?php

namespace App\Tables\AccountingTables;

class ReceivableTable
{
    /**
     * accounting_account_id solo se ofrece con accounting.advanced activo
     * (REQ-02.13) — CxC es un módulo base, no debe exponer una cuenta
     * contable de un módulo que puede estar apagado.
     */
    public static function allColumns(): array
    {
        return array_filter([
            'emission_date' => 'Fecha Emisión',
            'due_date' => 'Vencimiento',
            'document_number' => 'No. Factura',
            'client' => 'Cliente',
            'description' => 'Concepto',
            'total_amount' => 'Monto Original',
            'current_balance' => 'Saldo Pendiente',
            'accounting_account_id' => module_enabled('accounting.advanced') ? 'Cuenta Contable' : null,
            'status' => 'Estado',
            'updated_at' => 'Último Movimiento',
        ]);
    }

    public static function defaultDesktop(): array
    {
        return array_values(array_filter([
            'emission_date',
            'document_number',
            'client',
            'total_amount',
            'current_balance',
            'due_date',
            'status',
            module_enabled('accounting.advanced') ? 'accounting_account_id' : null,
        ]));
    }

    public static function defaultMobile(): array
    {
        // En móvil: Quién, cuánto debe y qué tan urgente es (estado)
        return ['client', 'current_balance', 'status'];
    }
}
