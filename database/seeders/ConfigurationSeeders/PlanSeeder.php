<?php

namespace Database\Seeders\ConfigurationSeeders;

use App\Models\Configuration\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Planes públicos reales de zertixpos.com. Cotizaciones no aparece en ningún
     * plan porque es base (docs/features/v1.1.0.md §5.3) — disponible siempre,
     * sin flag.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Emprendedor',
                'slug' => 'emprendedor',
                'price' => 29.00,
                'modules' => ['sales.ncf', 'sales.credit_notes_b04'],
            ],
            [
                'name' => 'PyME',
                'slug' => 'pyme',
                'price' => 59.00,
                'modules' => ['sales.ncf', 'sales.credit_notes_b04', 'inventory.advanced'],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 89.00,
                'modules' => [
                    'sales.ncf',
                    'sales.credit_notes_b04',
                    'sales.delivery_points',
                    'inventory.advanced',
                    'purchases.vendors',
                    'clients.field_assets', ],
            ],
        ];

        foreach ($plans as $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'price' => $data['price'], 'currency' => 'USD']
            );

            Plan::syncModules($plan->id, $data['modules']);
        }
    }
}
