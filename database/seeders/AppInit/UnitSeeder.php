<?php

namespace Database\Seeders\AppInit;

use App\Models\Products\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Libra',
                'abbreviation' => 'lb',
            ],
            [
                'name' => 'Funda',
                'abbreviation' => 'fnd',
            ],
            [
                'name' => 'Unidad',
                'abbreviation' => 'und',
            ],
            [
                'name' => 'Caja',
                'abbreviation' => 'cja',
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['name' => $unit['name']],
                [
                    'abbreviation' => $unit['abbreviation'],
                    'is_active' => true,
                ]
            );
        }
    }
}
