<?php

namespace App\Tables\SalesTables\Pos;

class PosTerminalTable
{
    /**
     * Definición de todas las columnas disponibles para las terminales POS.
     */
    public static function allColumns(): array
    {
        return [
            'id'                  => 'ID Terminal',
            'name'                => 'Nombre Terminal',
            'warehouse_id'        => 'Almacén Asociado',
            'cash_account_id'     => 'Cuenta Caja',
            'default_ncf_type_id' => 'Tipo NCF Defecto',
            'default_client_id'   => 'Cliente Defecto',
            'is_mobile'           => 'Es Móvil',
            'printer_format'      => 'Formato Impresión',
            'is_active'           => 'Estado',
            'created_at'          => 'Fecha Creación',
            'updated_at'          => 'Fecha Actualización',
        ];
    }

    /**
     * Columnas visibles por defecto en escritorio.
     */
    public static function defaultDesktop(): array
    {
        return [
            'id',
            'name',
            'warehouse_id',
            'cash_account_id',
            'printer_format',
            'is_active',
            'created_at',
        ];
    }

    /**
     * Columnas críticas para móviles.
     */
    public static function defaultMobile(): array
    {
        return [
            'name',
            'is_active',
            'printer_format',
        ];
    }
}