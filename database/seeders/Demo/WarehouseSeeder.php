<?php

namespace Database\Seeders\Demo;

use App\Models\Inventory\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Planta de Producción Principal',
                'type' => Warehouse::TYPE_STATIC,
                'address' => 'Zona Industrial, Calle 5, Edificio A',
                'description' => 'Fábrica principal donde se produce el hielo.',
                'is_active' => true,
            ],
            [
                'name' => 'Punto de Venta Fábrica',
                'type' => Warehouse::TYPE_POS,
                'address' => 'Entrada Principal de Planta',
                'description' => 'Ventas directas al público desde la fábrica.',
                'is_active' => true,
            ],
            [
                'name' => 'Camión de Ruta #01',
                'type' => Warehouse::TYPE_MOBILE,
                'address' => 'Placa: ABC-123',
                'description' => 'Unidad móvil para distribución local.',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $data) {
            $warehouse = Warehouse::create($data);
            // La creación de subcuenta contable ya no corre aquí (REQ-02.4/02.10) — un
            // almacén sembrado no debe depender de Contabilidad. Se genera solo cuando
            // corresponda (accounting.advanced activo, Fase 3), nunca desde el seeder base.
            $warehouse->generateCode();
        }
    }
}
