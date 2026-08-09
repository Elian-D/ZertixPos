<?php

namespace Database\Seeders\AppInit;

use App\Models\Configuration\ClientStateCategory;
use App\Models\Configuration\EstadosCliente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadosClienteSeeder extends Seeder
{
    public function run(): void
    {
        // Mapeo explícito Estado → Categoría
        $categoryMap = [
            'Activo' => 'OPERATIVO',
            'Inactivo' => 'BLOQUEO_TOTAL',
            'Suspendido' => 'BLOQUEO_TOTAL',
            'Moroso' => 'FINANCIERO_RESTRICTO',
            'Prospecto' => 'PRE_CLIENTE',
        ];

        $estados = [
            [
                'nombre' => 'Activo',
                'activo' => true,
                'clase_fondo' => 'bg-green-100',
                'clase_texto' => 'text-green-800',
            ],
            [
                'nombre' => 'Inactivo',
                'activo' => true,
                'clase_fondo' => 'bg-gray-100',
                'clase_texto' => 'text-gray-800',
            ],
            [
                'nombre' => 'Suspendido',
                'activo' => true,
                'clase_fondo' => 'bg-yellow-100',
                'clase_texto' => 'text-yellow-800',
            ],
            [
                'nombre' => 'Moroso',
                'activo' => true,
                'clase_fondo' => 'bg-red-100',
                'clase_texto' => 'text-red-800',
            ],
            [
                'nombre' => 'Prospecto',
                'activo' => true,
                'clase_fondo' => 'bg-blue-100',
                'clase_texto' => 'text-blue-800',
            ],
        ];

        DB::transaction(function () use ($estados, $categoryMap) {

            foreach ($estados as $estado) {

                $categoryCode = $categoryMap[$estado['nombre']] ?? null;

                if (! $categoryCode) {
                    throw new \RuntimeException(
                        "No hay categoría definida para el estado: {$estado['nombre']}"
                    );
                }

                $category = ClientStateCategory::where('code', $categoryCode)->first();

                if (! $category) {
                    throw new \RuntimeException(
                        "La categoría '{$categoryCode}' no existe. Ejecuta primero ClientStateCategorySeeder."
                    );
                }

                EstadosCliente::updateOrCreate(
                    ['nombre' => $estado['nombre']],
                    array_merge($estado, [
                        'client_state_category_id' => $category->id,
                    ])
                );
            }
        });
    }
}
