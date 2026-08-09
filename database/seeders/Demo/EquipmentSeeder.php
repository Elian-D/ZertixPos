<?php

namespace Database\Seeders\Demo;

use App\Models\Clients\Equipment;
use App\Models\Clients\PointOfSale;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $pointsOfSale = PointOfSale::all();

        if ($pointsOfSale->isEmpty()) {
            $this->command->warn('No hay Puntos de Venta para asignar equipos. Abortando.');

            return;
        }

        foreach ($pointsOfSale as $pos) {
            // Creamos entre 1 y 3 equipos para cada Punto de Venta
            Equipment::factory()
                ->count(1)
                ->create([
                    'point_of_sale_id' => $pos->id,
                ]);
        }
    }
}
