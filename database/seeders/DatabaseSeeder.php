<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Permission Seeders
            \Database\Seeders\PermissionSeeder\DashboardPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\RolesPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\UsersPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\ConfigPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\GeographyPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\ClientPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\POSPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\EquipmentPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\ProductsPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\InventoryPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\AccountingPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\SalesPermissionsSeeder::class,
            \Database\Seeders\PermissionSeeder\SalePosPermissionsSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,

            // Configuration Seeders
            \Database\Seeders\ConfigurationSeeders\InstallationModuleSeeder::class,

            // Accounting Seeders
            \Database\Seeders\AccountingSeeders\AccountingAccountSeeder::class,
            \Database\Seeders\AccountingSeeders\AccountingAccountRoleSeeder::class,
            \Database\Seeders\AccountingSeeders\DocumentTypeSeeder::class,

            \Database\Seeders\ConfigurationSeeders\ClientStateCategorySeeder::class,
            \Database\Seeders\ConfigurationSeeders\EstadosClienteSeeder::class,
            \Database\Seeders\ConfigurationSeeders\DiaSemanaSeeder::class,
            \Database\Seeders\ConfigurationSeeders\TipoPagoSeeder::class,
            \Database\Seeders\ConfigurationSeeders\ImpuestoSeeder::class,
            \Database\Seeders\ConfigurationSeeders\ConfiguracionGeneralSeeder::class,
            \Database\Seeders\ConfigurationSeeders\TaxIdentifierTypeSeeder::class,

            // Clients Configuration Seeders
            \Database\Seeders\ClientsConfigSeeders\BusinessTypeSeeder::class,
            \Database\Seeders\ClientsConfigSeeders\EquipmentTypeSeeder::class,
            \Database\Seeders\ClientsConfigSeeders\ClientSeeder::class,
            \Database\Seeders\ClientsConfigSeeders\PointOfSaleSeeder::class,
            \Database\Seeders\ClientsConfigSeeders\EquipmentSeeder::class,

            // Products Seeders
            \Database\Seeders\ProductsSeeders\CategorySeeder::class,
            \Database\Seeders\ProductsSeeders\UnitSeeder::class,
            \Database\Seeders\ProductsSeeders\ProductSeeder::class,

            // Inventory Seeders
            \Database\Seeders\InventorySeeders\WarehouseSeeder::class,
            \Database\Seeders\InventorySeeders\InventoryStockSeeder::class,

            // Sales Seeders
            \Database\Seeders\SalesSeeders\NcfTypeSeeder::class,
        ]);
    }
}
