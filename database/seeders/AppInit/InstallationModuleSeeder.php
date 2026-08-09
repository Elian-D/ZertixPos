<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstallationModuleSeeder extends Seeder
{
    /**
     * Todo módulo satélite registrado en config/modules.php arranca apagado —
     * "se apaga por defecto" (docs/features/v1.1.0.md). Qué módulos activar es
     * decisión de un `Plan` (Fase 5), no de este seeder — no se infiere nada a
     * partir de datos ya sembrados.
     */
    public function run(): void
    {
        foreach (array_keys(config('modules', [])) as $moduleKey) {
            DB::table('installation_modules')->updateOrInsert(
                ['module_key' => $moduleKey],
                ['is_enabled' => false, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
