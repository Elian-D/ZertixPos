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
            // Precio con impuesto incluido — lo que el cliente paga en caja, visible por
            // defecto (Fase 5, REQ-5.11). 'price' (neto, lo que se guarda) queda como
            // columna secundaria oculta por defecto para quien calcule margen vs. costo.
            'price_with_tax'    => 'Precio',
            'price'             => 'Precio Neto',
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
            'price_with_tax',
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
