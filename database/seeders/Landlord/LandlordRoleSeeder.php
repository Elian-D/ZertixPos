<?php

namespace Database\Seeders\Landlord;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class LandlordRoleSeeder extends Seeder
{
    public function run(): void
    {
        // `guard_name` explícito — sin esto Spatie lo crea contra el guard por
        // defecto (`web`), que en la conexión central ni siquiera existe.
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'landlord']);
    }
}
