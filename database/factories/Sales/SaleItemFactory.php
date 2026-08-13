<?php

namespace Database\Factories\Sales;

use App\Models\Products\Product;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $product = Product::query()->inRandomOrder()->first();
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = $product->price ?? fake()->randomFloat(2, 50, 500);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => $product?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'subtotal' => $quantity * $unitPrice,
        ];
    }
}
