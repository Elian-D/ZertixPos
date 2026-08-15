<?php

namespace App\Tables\AccountingTables;

class CollectionTable
{
    public static function allColumns(): array
    {
        return [
            'payment_date'   => 'Fecha',
            'receipt_number' => 'No. Recibo',
            'client'         => 'Cliente',
            'receivable'     => 'Factura/Doc',
            'tipo_pago'      => 'Método',
            'reference'      => 'Referencia',
            'amount'         => 'Monto Cobrado',
            'status'         => 'Estado',
            'created_by'     => 'Registrado por',
            'created_at'     => 'Fecha Registro',
        ];
    }

    public static function defaultDesktop(): array
    {
        return [
            'payment_date',
            'receipt_number',
            'client',
            'receivable',
            'tipo_pago',
            'reference',
            'amount',
            'status',
        ];
    }

    public static function defaultMobile(): array
    {
        // En móvil lo esencial: Cliente, monto y número de recibo
        return ['receipt_number', 'client', 'amount', 'status'];
    }
}
