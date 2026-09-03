<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.3, v1.3.0 Fase 3 — mapeo Plan de ZertixPOS -> Billing Plan real de
// PayPal (`P-XXXXXXXX`), aprovisionado una sola vez por plan comercial, no
// por tenant. `PayPalGateway::createSubscription()` lo lee de acá; nulo
// mientras el plan todavía no fue dado de alta en PayPal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('gateway_plan_id')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('gateway_plan_id');
        });
    }
};
