<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 4.9, REQ-4.9 — encontrado armando el correo/PDF de factura: derivar el
// plan de la factura vía `subscription->plan_id` (mutable) mostraba el plan
// ACTUAL de la suscripción, no el que de verdad se pagó ese día — un pago de
// PyME aparecía como "Plan Pro" después de un upgrade posterior. Una factura
// es un registro histórico, no debe cambiar retroactivamente por acciones
// futuras sobre la suscripción. Sin FK — mismo criterio que
// `subscriptions.plan_id`/`scheduled_plan_id`, que tampoco la tienen.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->after('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn('plan_id');
        });
    }
};
