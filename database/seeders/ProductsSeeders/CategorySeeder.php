<?php

namespace Database\Seeders\ProductsSeeders;

use App\Models\Products\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hielo',
                'description' => 'Diferentes presentaciones de hielo en funda y bloques.',
            ],
            [
                'name' => 'Agua',
                'description' => 'Agua purificada en distintas presentaciones.',
            ],
            [
                'name' => 'Accesorios',
                'description' => 'Bombas, dispensadores y otros artículos relacionados.',
            ],
            [
                'name' => 'Bebidas',
                'description' => 'Refrescos, jugos y bebidas frías para venta directa.',
            ],
            [
                'name' => 'Snacks',
                'description' => 'Golosinas y meriendas para venta al mostrador.',
            ],
            [
                'name' => 'Limpieza',
                'description' => 'Artículos de limpieza e higiene para el hogar.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }
    }
}