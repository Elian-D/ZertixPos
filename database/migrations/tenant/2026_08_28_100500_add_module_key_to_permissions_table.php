<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-2.7 punto 5 (v1.3.0) — filtra permisos de módulos satélite/flexibles
     * apagados en la UI de asignación de roles/usuarios.
     *
     * El plan original ponía esta columna en `permission_groups` (un
     * `module_key` por grupo). Ya no calza: REQ-2.5 consolidó 30 grupos 1:1
     * por recurso a 8 grupos por dominio (`docs/features/v1.3.0.md` §2.5), y
     * varios de esos 8 grupos mezclan permisos núcleo (siempre visibles) con
     * permisos de un módulo satélite/flexible — ej. el grupo "Clientes" trae
     * `clients.*` (núcleo) junto con `delivery_points.*`/`equipment.*`
     * (satélite). Un `module_key` a nivel de grupo no puede representar esa
     * mezcla. Se mueve a `permissions` — granularidad por permiso individual,
     * compatible con cualquier agrupación futura.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module_key')->nullable()->after('permission_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('module_key');
        });
    }
};
