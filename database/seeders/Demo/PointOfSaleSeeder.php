<?php

namespace Database\Seeders\Demo;

use App\Models\Clients\PointOfSale;
use Illuminate\Database\Seeder;

class PointOfSaleSeeder extends Seeder
{
    public function run(): void
    {
        // Creamos 150 puntos de venta
        PointOfSale::factory()->count(100)->create();
    }
}
