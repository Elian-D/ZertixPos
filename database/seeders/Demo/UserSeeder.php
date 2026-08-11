<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        $admin->assignRole('admin');
        $normal->assignRole('Usuario Genérico');
    }
}
