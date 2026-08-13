<?php

namespace App\Tables\AccountingTables;

class DocumentTypeTable
{
    public static function allColumns(): array
    {
        return [
            'name' => 'Nombre del Documento',
            'code' => 'Código / Sigla',
            'prefix' => 'Prefijo',
            'current_number' => 'Último Correlativo',
            'next_number' => 'Próximo Número', // Calculado: current + 1
            'is_active' => 'Estado',
            'updated_at' => 'Última Modificación',
        ];
    }

    public static function defaultDesktop(): array
    {
        return [
            'name',
            'code',
            'current_number',
            'is_active',
            'updated_at',
        ];
    }

    public static function defaultMobile(): array
    {
        // En móvil, con el nombre y el código basta para identificar el tipo
        return [
            'name',
            'code',
            'is_active',
        ];
    }
}
