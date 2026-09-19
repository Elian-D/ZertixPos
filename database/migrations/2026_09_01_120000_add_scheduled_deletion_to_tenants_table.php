<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.8, v1.3.0 Fase 3 — campo nuevo, no derivado de current_period_ends_at
// en vivo. El job de reconciliación (REQ-3.6), al marcar una suscripción
// past_due por primera vez, setea esto a now()->addDays(90) — una sola vez,
// no se recalcula en corridas posteriores. Se limpia (null) cuando el cliente
// paga antes de que se cumplan los 90 días (PayPalGateway, al confirmarse
// el pago). El borrado real (REQ-3.12) todavía no está implementado.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('scheduled_deletion_at')->nullable()->after('is_demo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('scheduled_deletion_at');
        });
    }
};
