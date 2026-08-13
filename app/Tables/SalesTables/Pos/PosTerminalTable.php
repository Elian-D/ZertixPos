<?php

namespace App\Tables\SalesTables\Pos;

class PosTerminalTable
{
    /**
     * Definición de todas las columnas disponibles para las terminales POS.
     * cash_account_id solo se ofrece con accounting.advanced activo
     * (REQ-02.16) — el checkout POS ya no depende de esa cuenta (REQ-02.9),
     * así que no tiene sentido mostrarla con el módulo apagado.
     */
    public static function allColumns(): array
    {
        return array_filter([
            'id' => 'ID Terminal',
            'name' => 'Nombre Terminal',
            'warehouse_id' => 'Almacén Asociado',
            'cash_account_id' => module_enabled('accounting.advanced') ? 'Cuenta Caja' : null,
            'default_ncf_type_id' => 'Tipo NCF Defecto',
            'default_client_id' => 'Cliente Defecto',
            'is_mobile' => 'Es Móvil',
            'printer_format' => 'Formato Impresión',
            'is_active' => 'Estado',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
        ]);
    }

    /**
     * Columnas visibles por defecto en escritorio.
     */
    public static function defaultDesktop(): array
    {
        return array_values(array_filter([
            'id',
            'name',
            'warehouse_id',
            module_enabled('accounting.advanced') ? 'cash_account_id' : null,
            'printer_format',
            'is_active',
            'created_at',
        ]));
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
