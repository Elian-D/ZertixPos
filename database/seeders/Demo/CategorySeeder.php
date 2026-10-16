<?php

namespace Database\Seeders\Demo;

use App\Models\Products\Category;
use Illuminate\Database\Seeder;

/**
 * Categorías del demo en dos niveles (v1.5.1 REQ-2): la principal agrupa y los productos
 * van en sus subcategorías. Los nombres de las subcategorías que usa ProductSeeder (Hielo,
 * Agua, Accesorios…) se mantienen.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Agua y Hielo' => [
                'Agua' => 'Agua purificada en distintas presentaciones.',
                'Hielo' => 'Diferentes presentaciones de hielo en funda y bloques.',
                'Accesorios' => 'Bombas, dispensadores y otros artículos relacionados.',
            ],
            'Colmado' => [
                'Bebidas' => 'Refrescos, jugos y bebidas frías para venta directa.',
                'Snacks' => 'Golosinas y meriendas para venta al mostrador.',
                'Limpieza' => 'Artículos de limpieza e higiene para el hogar.',
            ],
            'Otros' => [
                'Servicios' => 'Servicios sin control de inventario (instalación, flete, mano de obra, etc.).',
            ],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::updateOrCreate(['parent_id' => null, 'name' => $parentName], ['is_active' => true]);

            foreach ($children as $name => $description) {
                Category::updateOrCreate(
                    ['parent_id' => $parent->id, 'name' => $name],
                    ['description' => $description, 'is_active' => true],
                );
            }
        }
    }
}
