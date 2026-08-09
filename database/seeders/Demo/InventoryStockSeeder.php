<?php

namespace Database\Seeders\Demo;

use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;

class InventoryStockSeeder extends Seeder
{
    /**
     * Da existencia a todos los productos vendibles en los almacenes que
     * usan las terminales POS, para poder probar el Workspace con catálogo real.
     */
    public function run(): void
    {
        $warehouses = Warehouse::whereIn('type', [Warehouse::TYPE_POS, Warehouse::TYPE_STATIC])->get();
        $products = Product::where('is_stockable', true)->get();

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                InventoryStock::updateOrCreate(
                    ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                    [
                        'quantity' => random_int(50, 500),
                        'min_stock' => 10,
                    ]
                );
            }
        }
    }
}
