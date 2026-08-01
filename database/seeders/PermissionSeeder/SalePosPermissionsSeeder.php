<?php

namespace Database\Seeders\PermissionSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SalePosPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view pos terminals',
            'create pos terminals',
            'edit pos terminals',
            'delete pos terminals', // SoftDelete por seguridad técnica

            'pos sessions manage',
            'pos sessions history',

            'pos cash movements create', 
            'pos cash movements history',

            'pos config view', 
            'pos config update',

            'view quotes',
            'create quotes',
            'edit quotes',
            'convert quotes', // Para pasar a venta
            'cancel quotes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}