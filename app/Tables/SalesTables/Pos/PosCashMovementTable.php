<?php

namespace App\Tables\SalesTables\Pos;

class PosCashMovementTable
{
    /**
     * Definición de todas las columnas de la tabla pos_cash_movements.
     */
    public static function allColumns(): array
    {
        return [
            'id'                  => 'ID',
            'created_at'          => 'Fecha/Hora',
            'pos_session_id'      => 'Sesión',
            'user_id'             => 'Usuario/Cajero',
            'accounting_entry_id' => 'Asiento Contable',
            'accounting_account_id' => 'Cuenta Contable', // <--- AGREGADO
            'type'                => 'Tipo',
            'amount'              => 'Monto',
            'reason'              => 'Motivo/Razón',
            'reference'           => 'Referencia',
            'metadata'            => 'Metadatos',
        ];
    }

    /**
     * Columnas para vista de escritorio.
     */
    public static function defaultDesktop(): array
    {
        return [
            'id',
            'created_at',
            'user_id',
            'type',
            'amount',
            'reason',
            'accounting_entry_id',
        ];
    }

    /**
     * Columnas esenciales para vista móvil.
     */
    public static function defaultMobile(): array
    {
        return [
            'type',
            'amount',
            'reason',
        ];
    }
}