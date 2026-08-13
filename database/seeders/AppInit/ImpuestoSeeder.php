<?php

namespace Database\Seeders\AppInit;

use App\Models\Configuration\Impuesto;
use Illuminate\Database\Seeder;

class ImpuestoSeeder extends Seeder
{
    public function run(): void
    {
        Impuesto::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Impuesto General',
                'tipo' => Impuesto::TIPO_PORCENTAJE,
                'valor' => 0,
                'es_incluido' => false,
            ]
        );
    }
}
