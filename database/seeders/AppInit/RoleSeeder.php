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
        // Creación de roles

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'Usuario Genérico']);

        // Asignar todos los permisos al rol admin
        $admin->syncPermissions(Permission::all());

        // Asignar todos los permisos al rol admin
        $user->syncPermissions('view dashboard');
    }
}
