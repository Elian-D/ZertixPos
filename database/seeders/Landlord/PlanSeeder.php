<?php

namespace Database\Seeders\Landlord;

use App\Models\Configuration\Plan;
use Illuminate\Database\Seeder;

/**
 * Movido de Database\Seeders\AppInit (tenant) a acá (REQ-3.4, v1.3.0 Fase 3)
 * — `plans`/`plan_module` son catálogo de ZertixPOS, no dato propio de cada
 * negocio. Corre con `db:seed --class`, no con `tenants:seed`.
 *
 * Idempotente por slug (updateOrCreate) y no toca `gateway_plan_id` — correr
 * esto de nuevo después de que `paypal:sync-plans` ya haya llenado esa
 * columna no la borra.
 */
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
                'users_limit' => 1, // un solo dueño/operador — REQ-05.6
                'features' => [
                    'Punto de venta rápido',
                    'Facturación electrónica estándar',
                    'Inventario básico automático',
                    'Control de caja diario',
                    'Envío de facturas por WhatsApp',
                ],
                'modules' => ['sales.ncf'],
            ],
            [
                'name' => 'PyME',
                'slug' => 'pyme',
                'price' => 59.00,
                'users_limit' => null, // multiusuario, sin techo
                'features' => [
                    'Reportes financieros avanzados',
                    'Inventario inteligente con alertas',
                    'Multiusuario con roles',
                    'Margen de ganancia por producto',
                    'Automatización WhatsApp ilimitada',
                ],
                'modules' => ['sales.ncf', 'inventory.advanced'],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 89.00,
                'users_limit' => null,
                'features' => [
                    'Panel ejecutivo con métricas avanzadas',
                    'Rutas con cálculo de distancia',
                    'API de integración externa',
                    'Automatización total ilimitada',
                    'Soporte prioritario',
                ],
                'modules' => [
                    'sales.ncf',
                    'sales.delivery_points',
                    'inventory.advanced',
                    'purchases.vendors',
                    'clients.field_assets', ],
            ],
        ];

        foreach ($plans as $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'currency' => 'USD',
                    'users_limit' => $data['users_limit'],
                    'features' => $data['features'],
                ]
            );

            Plan::syncModules($plan->id, $data['modules']);
        }
    }
}
