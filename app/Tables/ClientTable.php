<?php

namespace App\Tables;

class ClientTable
{
    public static function allColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Nombre Cliente',
            'tax_identifier_types' => 'Tipo ID Fiscal',
            'tax_id' => 'ID Fiscal',
            'type' => 'Tipo',
            'balance' => 'Saldo Pendiente', // Nueva
            'credit_limit' => 'Límite Crédito',  // Nueva
            'email' => 'Email',
            'phone' => 'Teléfono',
            'state' => 'Estado/Provincia',
            'city' => 'Ciudad',
            'address' => 'Dirección',
            'is_active' => 'Estado',
            'created_at' => 'Fecha Creación',
        ];
    }

    public static function defaultDesktop(): array
    {
        // Añadimos balance para control financiero inmediato
        return ['id', 'name', 'tax_id', 'balance', 'credit_limit', 'is_active'];
    }

    public static function defaultMobile(): array
    {
        return ['name', 'balance']; // En móvil, el saldo es vital
    }
}
