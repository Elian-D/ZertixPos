<?php

namespace Database\Seeders\AppInit;

use App\Models\Configuration\DiaSemana;
use Illuminate\Database\Seeder;

class DiaSemanaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dias = [
            ['nombre' => 'Lunes',     'codigo' => 'mon', 'orden' => 1],
            ['nombre' => 'Martes',    'codigo' => 'tue', 'orden' => 2],
            ['nombre' => 'Miércoles', 'codigo' => 'wed', 'orden' => 3],
            ['nombre' => 'Jueves',    'codigo' => 'thu', 'orden' => 4],
            ['nombre' => 'Viernes',   'codigo' => 'fri', 'orden' => 5],
            ['nombre' => 'Sábado',    'codigo' => 'sat', 'orden' => 6],
            ['nombre' => 'Domingo',   'codigo' => 'sun', 'orden' => 7],
        ];

        foreach ($dias as $dia) {
            DiaSemana::updateOrCreate(
                ['codigo' => $dia['codigo']], // condición única
                [
                    'nombre' => $dia['nombre'],
                    'orden' => $dia['orden'],
                    'estado' => true,
                ]
            );
        }
    }
}
