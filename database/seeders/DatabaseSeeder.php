<?php

namespace Database\Seeders;

use Database\Seeders\AppInit\AccountingAccountRoleSeeder;
use Database\Seeders\AppInit\AccountingAccountSeeder;
use Database\Seeders\AppInit\BusinessTypeSeeder;
use Database\Seeders\AppInit\ClientSeeder;
use Database\Seeders\AppInit\ClientStateCategorySeeder;
use Database\Seeders\AppInit\ConfiguracionGeneralSeeder;
use Database\Seeders\AppInit\DiaSemanaSeeder;
use Database\Seeders\AppInit\DocumentTypeSeeder;
use Database\Seeders\AppInit\EstadosClienteSeeder;
use Database\Seeders\AppInit\GeoDataSeeder;
use Database\Seeders\AppInit\ImpuestoSeeder;
use Database\Seeders\AppInit\InstallationModuleSeeder;
use Database\Seeders\AppInit\NcfTypeSeeder;
use Database\Seeders\AppInit\PermissionSeeder;
use Database\Seeders\AppInit\PlanSeeder;
use Database\Seeders\AppInit\PosSettingSeeder;
use Database\Seeders\AppInit\RoleSeeder;
use Database\Seeders\AppInit\TipoPagoSeeder;
use Database\Seeders\AppInit\UnitSeeder;
use Database\Seeders\AppInit\UserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Solo `core` — el catálogo de ejemplo
     * (categorías, productos, almacenes, puntos de venta, equipos, clientes
     * ficticios) vive en `zertix:seed-demo` (REQ-07.9, aún no construido).
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class, // consolidado — antes 13 clases en PermissionSeeder/* — debe correr antes que RoleSeeder
            RoleSeeder::class, // syncPermissions() necesita que los permisos ya existan
            UserSeeder::class, // credenciales hardcodeadas — necesario hasta que exista el Wizard (Fase 8)

            // Datos geográficos (RD-only, Fase 6)
            GeoDataSeeder::class,

            PlanSeeder::class,
            InstallationModuleSeeder::class,
            ClientStateCategorySeeder::class,
            EstadosClienteSeeder::class,
            DiaSemanaSeeder::class,
            TipoPagoSeeder::class,
            ImpuestoSeeder::class,
            ConfiguracionGeneralSeeder::class, // sin 'Empresa Demo' (REQ-07.4)

            AccountingAccountSeeder::class,
            AccountingAccountRoleSeeder::class,
            DocumentTypeSeeder::class,

            BusinessTypeSeeder::class,
            ClientSeeder::class, // solo "Consumidor Final" (REQ-07.3)

            UnitSeeder::class,
            NcfTypeSeeder::class,

            // Depende de que ClientSeeder ya haya corrido (usa el tax_id de
            // Consumidor Final) — huérfano hasta ahora, nunca se registraba (REQ-07.5).
            PosSettingSeeder::class,
        ]);
    }
}
