<?php

namespace App\Tables;

class PointOfSaleTable
{
    public static function allColumns(): array
    {
        return [
            'code' => 'Código',
            'name' => 'Nombre PDV',
            'client_id' => 'Cliente',
            'business_type_id' => 'Tipo Negocio',
            'provincia_id' => 'Provincia',
            'city' => 'Ciudad',
            'contact_name' => 'Contacto',
            'contact_phone' => 'Teléfono Contacto',
            'active' => 'Estado',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    public static function defaultDesktop(): array
    {
        return ['code', 'name', 'client_id', 'business_type_id', 'active'];
    }

    public static function defaultMobile(): array
    {
        return ['code', 'name'];
    }
}
