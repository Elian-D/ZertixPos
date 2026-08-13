<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstallationModuleSeeder extends Seeder
{
    /**
     * Todo módulo satélite arranca apagado — qué activar es decisión de un `Plan`
     * (Fase 5, `Plan::assignTo()`), no de este seeder. Los flexibles (REQ-10.4)
     * arrancan encendidos una sola vez acá — de ahí en adelante `Plan::assignTo()`
     * nunca los toca (filtra solo category === 'satellite'), y solo los cambia el
     * dueño desde "Funcionalidades del Sistema" (REQ-10.6/10.8).
     */
    public function run(): void
    {
        foreach (config('modules', []) as $moduleKey => $module) {
            DB::table('installation_modules')->updateOrInsert(
                ['module_key' => $moduleKey],
                [
                    'is_enabled' => $module['category'] === 'base_flexible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
