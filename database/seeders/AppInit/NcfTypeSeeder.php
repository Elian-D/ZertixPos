<?php

namespace Database\Seeders\AppInit;

use App\Models\Sales\Ncf\NcfType;
use Illuminate\Database\Seeder;

class NcfTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // --- COMPROBANTES ESTÁNDAR (Serie B) ---
            // Se agrega "Factura de" para que en el ticket se lea correctamente la denominación
            ['name' => 'Factura de Crédito Fiscal', 'prefix' => 'B', 'code' => '01', 'requires_rnc' => true, 'is_electronic' => false],
            ['name' => 'Factura de Consumo', 'prefix' => 'B', 'code' => '02', 'requires_rnc' => false, 'is_electronic' => false],
            ['name' => 'Nota de Débito', 'prefix' => 'B', 'code' => '03', 'requires_rnc' => true, 'is_electronic' => false],
            ['name' => 'Nota de Crédito', 'prefix' => 'B', 'code' => '04', 'requires_rnc' => true, 'is_electronic' => false],
            ['name' => 'Factura de Regímenes Especiales', 'prefix' => 'B', 'code' => '14', 'requires_rnc' => true, 'is_electronic' => false],
            ['name' => 'Factura Gubernamental', 'prefix' => 'B', 'code' => '15', 'requires_rnc' => true, 'is_electronic' => false],

            // --- COMPROBANTES ELECTRÓNICOS (Serie E) ---
            // Los electrónicos ya suelen llevar el nombre completo por estándar e-CF
            ['name' => 'Factura de Crédito Fiscal Electrónica', 'prefix' => 'E', 'code' => '31', 'requires_rnc' => true, 'is_electronic' => true],
            ['name' => 'Factura de Consumo Electrónica', 'prefix' => 'E', 'code' => '32', 'requires_rnc' => false, 'is_electronic' => true],
            ['name' => 'Nota de Débito Electrónica', 'prefix' => 'E', 'code' => '33', 'requires_rnc' => true, 'is_electronic' => true],
            ['name' => 'Nota de Crédito Electrónica', 'prefix' => 'E', 'code' => '34', 'requires_rnc' => true, 'is_electronic' => true],
            ['name' => 'Factura de Regímenes Especiales Electrónica', 'prefix' => 'E', 'code' => '44', 'requires_rnc' => true, 'is_electronic' => true],
            ['name' => 'Factura Gubernamental Electrónica', 'prefix' => 'E', 'code' => '45', 'requires_rnc' => true, 'is_electronic' => true],
        ];

        foreach ($types as $type) {
            NcfType::updateOrCreate(
                ['code' => $type['code'], 'prefix' => $type['prefix']],
                $type
            );
        }
    }
}
