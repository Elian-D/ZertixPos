<?php

namespace App\Tables;

class WarehouseTable
{
    /**
     * accounting_account_id solo existe como columna ofrecible con
     * accounting.advanced activo (REQ-02.12) — sin esto, el selector de
     * columnas y el desktop por defecto ofrecen una cuenta contable de un
     * módulo apagado.
     */
    public static function allColumns(): array
    {
        return array_filter([
            'name' => 'Nombre',
            'types' => 'Tipo',
            'accounting_account_id' => module_enabled('accounting.advanced') ? 'Cuenta Contable' : null,
            'address' => 'Ubicación',
            'description' => 'Descripción',
            'is_active' => 'Estado',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Última Actualización',
        ]);
    }

    public static function defaultDesktop(): array
    {
        return array_values(array_filter([
            'name',
            'types',
            module_enabled('accounting.advanced') ? 'accounting_account_id' : null,
            'is_active',
        ]));
    }

    public static function defaultMobile(): array
    {
        return ['name'];
    }
}
