<?php

namespace Database\Seeders\Demo;

use App\Models\Clients\EquipmentType; // Asegúrate de que el path del modelo sea correcto
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tiposEquipos = [
            'Freezer',
            'Anaquel',
        ];

        foreach ($tiposEquipos as $nombre) {
            EquipmentType::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'activo' => true,
                    'prefix' => EquipmentType::makePrefix($nombre),
                ]
            );
        }
    }
}
