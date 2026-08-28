<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Copy público (zertixpos.com) — distinto de plan_module, que es la
            // lista técnica de módulos que assignTo() copia a installation_modules.
            // La UI del Wizard/vitrina no debe mostrar claves internas del sistema.
            $table->json('features')->nullable()->after('currency');

            // null = sin techo. Solo Emprendedor lo tiene (pensado para un único
            // dueño/operador) — ver REQ-05.6.
            $table->unsignedTinyInteger('users_limit')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['features', 'users_limit']);
        });
    }
};
