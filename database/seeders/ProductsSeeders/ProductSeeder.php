<?php

namespace Database\Seeders\ProductsSeeders;

use App\Models\Products\Product;
use App\Models\Products\Category;
use App\Models\Products\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtener los IDs necesarios
        $catHielo = Category::where('name', 'Hielo')->first()->id;
        $catAgua = Category::where('name', 'Agua')->first()->id;
        $catAccesorios = Category::where('name', 'Accesorios')->first()->id;
        $catBebidas = Category::where('name', 'Bebidas')->first()->id;
        $catSnacks = Category::where('name', 'Snacks')->first()->id;
        $catLimpieza = Category::where('name', 'Limpieza')->first()->id;

        $unitFunda = Unit::where('abbreviation', 'fnd')->first()->id;
        $unitLibra = Unit::where('abbreviation', 'lb')->first()->id;
        $unitUnidad = Unit::where('abbreviation', 'und')->first()->id;
        $unitCaja = Unit::where('abbreviation', 'cja')->first()->id;

        $products = [
            // Hielo
            [
                'category_id'  => $catHielo,
                'unit_id'      => $unitFunda,
                'name'         => 'Funda de Hielo 10lb',
                'sku'          => 'PRD-00001',
                'description'  => 'Funda de hielo cristalino de 10 libras.',
                'price'        => 150.00,
                'cost'         => 45.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catHielo,
                'unit_id'      => $unitLibra,
                'name'         => 'Hielo al granel (Libra)',
                'sku'          => 'PRD-00002',
                'description'  => 'Venta de hielo por peso para eventos.',
                'price'        => 15.00,
                'cost'         => 5.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catHielo,
                'unit_id'      => $unitFunda,
                'name'         => 'Funda de Hielo 5lb',
                'sku'          => 'PRD-00003',
                'description'  => 'Funda de hielo cristalino de 5 libras.',
                'price'        => 80.00,
                'cost'         => 25.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catHielo,
                'unit_id'      => $unitFunda,
                'name'         => 'Bloque de Hielo 25lb',
                'sku'          => 'PRD-00004',
                'description'  => 'Bloque grande de hielo para neveras portátiles.',
                'price'        => 350.00,
                'cost'         => 110.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],

            // Agua
            [
                'category_id'  => $catAgua,
                'unit_id'      => $unitUnidad,
                'name'         => 'Botellón de Agua 5 Galones',
                'sku'          => 'PRD-00005',
                'description'  => 'Botellón retornable de agua purificada.',
                'price'        => 120.00,
                'cost'         => 40.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAgua,
                'unit_id'      => $unitUnidad,
                'name'         => 'Botella de Agua 500ml',
                'sku'          => 'PRD-00006',
                'description'  => 'Botella individual de agua purificada.',
                'price'        => 25.00,
                'cost'         => 8.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAgua,
                'unit_id'      => $unitCaja,
                'name'         => 'Caja de Agua 500ml (24 uds)',
                'sku'          => 'PRD-00007',
                'description'  => 'Caja con 24 botellas individuales de agua.',
                'price'        => 480.00,
                'cost'         => 160.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAgua,
                'unit_id'      => $unitUnidad,
                'name'         => 'Garrafón de Agua 1 Galón',
                'sku'          => 'PRD-00008',
                'description'  => 'Garrafón mediano de agua purificada.',
                'price'        => 60.00,
                'cost'         => 20.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],

            // Accesorios
            [
                'category_id'  => $catAccesorios,
                'unit_id'      => $unitUnidad,
                'name'         => 'Bomba Manual para Botellón',
                'sku'          => 'PRD-00009',
                'description'  => 'Bomba dispensadora manual para botellones de agua.',
                'price'        => 250.00,
                'cost'         => 90.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAccesorios,
                'unit_id'      => $unitUnidad,
                'name'         => 'Dispensador Eléctrico de Agua',
                'sku'          => 'PRD-00010',
                'description'  => 'Dispensador eléctrico frío/caliente para botellón.',
                'price'        => 3500.00,
                'cost'         => 2200.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAccesorios,
                'unit_id'      => $unitUnidad,
                'name'         => 'Nevera Portátil 20L',
                'sku'          => 'PRD-00011',
                'description'  => 'Cooler portátil para transportar hielo y bebidas.',
                'price'        => 1200.00,
                'cost'         => 750.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catAccesorios,
                'unit_id'      => $unitUnidad,
                'name'         => 'Vaso Térmico 16oz',
                'sku'          => 'PRD-00012',
                'description'  => 'Vaso térmico reutilizable de doble pared.',
                'price'        => 180.00,
                'cost'         => 70.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],

            // Bebidas
            [
                'category_id'  => $catBebidas,
                'unit_id'      => $unitUnidad,
                'name'         => 'Refresco Cola 591ml',
                'sku'          => 'PRD-00013',
                'description'  => 'Refresco de cola en botella individual.',
                'price'        => 45.00,
                'cost'         => 22.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catBebidas,
                'unit_id'      => $unitUnidad,
                'name'         => 'Jugo de Naranja 1L',
                'sku'          => 'PRD-00014',
                'description'  => 'Jugo de naranja natural embotellado.',
                'price'        => 90.00,
                'cost'         => 45.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catBebidas,
                'unit_id'      => $unitUnidad,
                'name'         => 'Bebida Energética 473ml',
                'sku'          => 'PRD-00015',
                'description'  => 'Bebida energizante en lata.',
                'price'        => 110.00,
                'cost'         => 60.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catBebidas,
                'unit_id'      => $unitUnidad,
                'name'         => 'Agua de Coco 330ml',
                'sku'          => 'PRD-00016',
                'description'  => 'Agua de coco natural en lata.',
                'price'        => 85.00,
                'cost'         => 40.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],

            // Snacks
            [
                'category_id'  => $catSnacks,
                'unit_id'      => $unitUnidad,
                'name'         => 'Papitas Fritas 45g',
                'sku'          => 'PRD-00017',
                'description'  => 'Funda individual de papitas fritas saladas.',
                'price'        => 35.00,
                'cost'         => 15.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catSnacks,
                'unit_id'      => $unitUnidad,
                'name'         => 'Barra de Chocolate 40g',
                'sku'          => 'PRD-00018',
                'description'  => 'Barra individual de chocolate con leche.',
                'price'        => 55.00,
                'cost'         => 28.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catSnacks,
                'unit_id'      => $unitUnidad,
                'name'         => 'Galletas Surtidas 100g',
                'sku'          => 'PRD-00019',
                'description'  => 'Paquete de galletas dulces surtidas.',
                'price'        => 60.00,
                'cost'         => 30.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catSnacks,
                'unit_id'      => $unitUnidad,
                'name'         => 'Maní Salado 50g',
                'sku'          => 'PRD-00020',
                'description'  => 'Funda de maní salado tostado.',
                'price'        => 40.00,
                'cost'         => 18.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],

            // Limpieza
            [
                'category_id'  => $catLimpieza,
                'unit_id'      => $unitUnidad,
                'name'         => 'Jabón Líquido para Manos 500ml',
                'sku'          => 'PRD-00021',
                'description'  => 'Jabón líquido antibacterial para manos.',
                'price'        => 150.00,
                'cost'         => 70.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catLimpieza,
                'unit_id'      => $unitUnidad,
                'name'         => 'Desinfectante Multiusos 1L',
                'sku'          => 'PRD-00022',
                'description'  => 'Desinfectante líquido de uso general.',
                'price'        => 180.00,
                'cost'         => 85.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
            [
                'category_id'  => $catLimpieza,
                'unit_id'      => $unitUnidad,
                'name'         => 'Alcohol Gel 250ml',
                'sku'          => 'PRD-00023',
                'description'  => 'Gel antibacterial para manos.',
                'price'        => 120.00,
                'cost'         => 55.00,
                'is_active'    => true,
                'is_stockable' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']], // Buscamos por SKU para no duplicar
                array_merge($product, ['slug' => Str::slug($product['name'])])
            );
        }
    }
}