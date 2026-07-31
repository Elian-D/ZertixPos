<?php

namespace App\Tables\SalesTables;

class SaleTable
{
    public static function allColumns(): array
    {
        return [
            'sale_date'       => 'Fecha',
            'number'          => 'Folio / Número',
            'client_id'       => 'Cliente',
            'warehouse_id'    => 'Almacén',
            'pos_terminal_id' => 'Terminal POS', // NUEVO
            'pos_session_id'  => 'Sesión POS',   // NUEVO
            'payment_type'    => 'Tipo de Venta',
            'tipo_pago_id'    => 'Método de Pago',
            'total_amount'    => 'Total',
            'status'          => 'Estado',
            'user_id'         => 'Vendedor',
            'notes'           => 'Notas',
            'created_at'      => 'Fecha Registro',
            'updated_at'      => 'Última Actualización',
        ];
    }

    public static function defaultDesktop(): array
    {
        return [
            'sale_date',
            'number',
            'client_id',
            'pos_terminal_id', // Agregado por defecto para trazabilidad
            'payment_type',
            'tipo_pago_id',
            'total_amount',
            'status',
        ];
    }

    public static function defaultMobile(): array
    {
        return [
            'client_id', 
            'total_amount', 
            'payment_type'
        ];
    }
}