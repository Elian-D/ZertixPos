<?php

namespace App\Tables;

class ProductTable
{
    public static function allColumns(): array
    {
        return [
            'name'              => 'Nombre',
            'image_path'        => 'Imagen',
            'category_id'       => 'Categoría',
            'description'       => 'Descripción',
            'price'             => 'Precio',
            'cost'              => 'Costo',
            'unit_id'           => 'Unidad de Medida',
            'is_active'         => 'Estado',
            'is_stockable'      => 'Tipo',
            'created_at'        => 'Fecha Creación',
            'updated_at'        => 'Última Actualización',
        ];
    }

    public static function defaultDesktop(): array
    {
        return [
            'name',
            'image_path',
            'price',
            'is_active',
            'is_stockable',
        ];
    }

    public static function defaultMobile(): array
    {
        return [
            'name'
        ];
    }
}
