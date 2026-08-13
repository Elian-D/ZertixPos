<?php

namespace Database\Factories\Clients;

use App\Models\Clients\Equipment;
use App\Models\Clients\EquipmentType;
use App\Models\Clients\PointOfSale;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'point_of_sale_id' => PointOfSale::inRandomOrder()->value('id'),
            'equipment_type_id' => EquipmentType::inRandomOrder()->value('id'),
            'serial_number' => $this->faker->unique()->bothify('SN-####-????'),
            'name' => $this->faker->randomElement([
                'Freezer Principal',
                'Freezer Secundario',
                'Anaquel Bebidas',
                'Anaquel Snacks',
                'Nevera Exhibidora',
                'Congelador Horizontal',
                'Dispensador de Agua',
            ]),
            'model' => $this->faker->bothify('MOD-###??'),
            'notes' => $this->faker->sentence(),
            'active' => true,
            'code' => null, // IMPORTANTE
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Equipment $equipment) {
            $equipment->generateCode();
        });
    }
}
