<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-2.7 (v1.3.0), mismo razonamiento que el tenant `users`: un `Admin`
     * (landlord) también puede tener acciones auditadas del lado central a
     * futuro (Fase 3+, suscripciones/pagos) — se agrega el trait desde ya
     * para no repetir el mismo error de hard-delete después.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
