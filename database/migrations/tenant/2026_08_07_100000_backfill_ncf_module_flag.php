<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $usaNcf = DB::table('configuraciones_generales')->value('usa_ncf');

        DB::table('installation_modules')->updateOrInsert(
            ['module_key' => 'sales.ncf'],
            ['is_enabled' => (bool) $usaNcf, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        // No revertimos el flag — la columna usa_ncf original se restaura (o no)
        // en la migración que la dropea (REQ-04.3), no aquí.
    }
};
