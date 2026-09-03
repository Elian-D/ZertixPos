<?php

namespace Database\Seeders\Landlord;

use Illuminate\Database\Seeder;

/**
 * Seeder raíz del landlord — corre con `db:seed` normal contra la conexión
 * central, nunca con `tenants:seed`. NO usar `db:seed` sin `--class`: el
 * comando sin argumentos apunta a Database\Seeders\DatabaseSeeder, que es el
 * seeder de NEGOCIO (pensado para `tenants:seed`) y reventaría contra la
 * central, que no tiene esas tablas (REQ-1.7).
 *
 *   sail artisan db:seed --class="Database\Seeders\Landlord\LandlordSeeder"
 */
class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LandlordRoleSeeder::class,
            LandlordAdminSeeder::class,
            PlanSeeder::class, // REQ-3.4 — catálogo de planes, movido de tenant
        ]);
    }
}
