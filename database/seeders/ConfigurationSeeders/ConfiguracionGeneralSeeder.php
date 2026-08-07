<?php

namespace Database\Seeders\ConfigurationSeeders;

use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Geo\Province;
use Illuminate\Database\Seeder;

class ConfiguracionGeneralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $distritoNacional = Province::where('name', 'Distrito Nacional')->value('id')
            ?? Province::query()->value('id');

        if (! $distritoNacional) {
            throw new \RuntimeException('No hay provincias sembradas — corre GeoDataSeeder primero.');
        }

        ConfiguracionGeneral::updateOrCreate(
            ['id' => 1],
            [
                'nombre_empresa' => 'Empresa Demo',
                'provincia_id' => $distritoNacional,
                'municipio_id' => null, // Se define luego desde el panel
                'impuesto_id' => 1,
            ]
        );
    }
}
