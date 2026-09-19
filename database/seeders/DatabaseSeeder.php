<?php

namespace Database\Seeders;

use Database\Seeders\AppInit\AccountingAccountRoleSeeder;
use Database\Seeders\AppInit\AccountingAccountSeeder;
use Database\Seeders\AppInit\BusinessTypeSeeder;
use Database\Seeders\AppInit\ClientSeeder;
use Database\Seeders\AppInit\ConfiguracionGeneralSeeder;
use Database\Seeders\AppInit\DocumentTypeSeeder;
use Database\Seeders\AppInit\GeoDataSeeder;
use Database\Seeders\AppInit\InstallationModuleSeeder;
use Database\Seeders\AppInit\NcfTypeSeeder;
use Database\Seeders\AppInit\PermissionSeeder;
use Database\Seeders\AppInit\PosSettingSeeder;
use Database\Seeders\AppInit\RoleSeeder;
use Database\Seeders\AppInit\TipoPagoSeeder;
use Database\Seeders\AppInit\UnitSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Solo `core` — el catálogo de ejemplo
     * (categorías, productos, almacenes, puntos de venta, equipos, clientes
     * ficticios, usuarios de fábrica) vive en `zertix:seed-demo`. Ningún
     * usuario se crea aquí — la instalación real lo hace el Wizard (Fase 8,
     * REQ-07.13): sin admin todavía, `ConfiguracionGeneral.nombre_empresa`
     * queda vacío y `EnsureInstallationWizardCompleted` redirige al wizard.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class, // consolidado — antes 13 clases en PermissionSeeder/* — debe correr antes que RoleSeeder
            RoleSeeder::class, // syncPermissions() necesita que los permisos ya existan — el usuario lo crea el Wizard, no un seeder

            // Datos geográficos (RD-only, Fase 6)
            GeoDataSeeder::class,

            // PlanSeeder ya NO corre acá — plans/plan_module se movieron a
            // landlord (REQ-3.4, v1.3.0 Fase 3). Corre vía
            // Database\Seeders\Landlord\PlanSeeder, con db:seed --class.
            InstallationModuleSeeder::class,
            TipoPagoSeeder::class,
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
