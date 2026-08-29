<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creación de rol Admin
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // Asignar todos los permisos al rol admin
        $admin->syncPermissions(Permission::all());
    }
}
