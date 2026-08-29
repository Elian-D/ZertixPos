<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Usuarios de fábrica hardcodeados (admin@local.com/usuario@local.com,
 * contraseña 12345678) — solo para desarrollo local, vía zertix:seed-demo.
 * La instalación real crea su administrador desde el Wizard (REQ-08.2/08.5),
 * con sus propias credenciales; no depende de este seeder (REQ-07.13).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'),
            ]
        );

        $normal = User::firstOrCreate(
            ['email' => 'usuario@local.com'],
            [
                'name' => 'Usuario Normal',
                'password' => Hash::make('12345678'),
            ]
        );

        // 'Usuario Genérico' ya no se siembra en RoleSeeder (init real: solo
        // 'admin', ver AppInit/RoleSeeder.php) — este seeder de demo se lo
        // crea a sí mismo si hace falta, para no depender de un rol que el
        // init real ya no garantiza. Mínimo permiso real (dashboard.view),
        // mismo criterio que tenía antes en RoleSeeder.
        $normalRole = Role::firstOrCreate(['name' => 'Usuario Genérico']);
        if ($normalRole->permissions->isEmpty()) {
            $normalRole->syncPermissions('dashboard.view');
        }

        $admin->assignRole('admin');
        $normal->assignRole($normalRole);
    }
}
