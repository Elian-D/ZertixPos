<?php

namespace Database\Seeders\Landlord;

use App\Models\Landlord\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Primer Admin del landlord — credenciales de arranque hardcodeadas, mismo
 * criterio que Database\Seeders\Demo\UserSeeder (usuarios de fábrica para
 * desarrollo). Cambiar la contraseña real en el primer login antes de
 * exponer el Panel de Súper Admin (Fase 5) a producción.
 */
class LandlordAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'super@zertixpos.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $role = Role::where('name', 'Super Admin')->where('guard_name', 'landlord')->first();

        if ($role) {
            $admin->assignRole($role);
        }
    }
}
