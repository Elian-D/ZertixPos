<?php

namespace Database\Seeders\AppInit;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // USUARIO ADMIN
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'), // cámbialo si quieres
            ]
        );

        // USUARIO NORMAL
        $normal = User::firstOrCreate(
            ['email' => 'usuario@local.com'],
            [
                'name' => 'Usuario Normal',
                'password' => Hash::make('12345678'),
            ]
        );

        // Asignacion de roles
        $admin->assignRole('admin');
        $normal->assignRole('Usuario Genérico');
    }
}
