<?php

namespace App\Tables\SalesTables;

class QuoteTable
{
    /**
     * Definición de todas las columnas disponibles para el motor de cotizaciones.
     */
    public static function allColumns(): array
    {
        return [
            'id'              => 'ID',
            'created_at'      => 'Fecha Emisión',
            'customer_id'     => 'Cliente',
            'user_id'         => 'Vendedor',
            'origin'          => 'Origen',      // POS o Backoffice
            'status'          => 'Estado',      // Draft, Approved, etc.
            'total'           => 'Monto Total',
            'expires_at'      => 'Vencimiento',
            'sale_id'         => 'Venta Ref.',   // Link a la venta si está convertida
            'pos_terminal_id' => 'Terminal',
            'notes'           => 'Observaciones',
            'updated_at'      => 'Última Modificación',
        ];
    }

    /**
     * Columnas visibles por defecto en escritorio.
     * Priorizamos trazabilidad y finanzas.
     */
    public static function defaultDesktop(): array
    {
        return [
            'created_at',
            'customer_id',
            'origin',
            'total',
            'status',
            'expires_at',
            'sale_id',
        ];
    }

    /**
     * Columnas críticas para vista móvil.
     */
    public static function defaultMobile(): array
    {
        return [
            'id',
            'customer_id',
            'total',
            'status',
        ];
    }
}