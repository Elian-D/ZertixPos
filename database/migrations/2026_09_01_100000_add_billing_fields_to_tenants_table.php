<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.4, v1.3.0 Fase 3 — plan_id se mueve acá desde
// `configuraciones_generales` (tenant) + campos de gateway nuevos, para que
// el Súper Admin (REQ-5.1) los lea sin conectarse a la base de cada tenant.
// `is_demo` adelantado de REQ-3.9: el middleware de REQ-3.5 ya lo necesita
// como excepción de bloqueo, aunque el aprovisionamiento real del tenant
// demo todavía no esté construido — evita tener que volver a tocar
// EnsureSubscriptionActive cuando REQ-3.9 se implemente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained('plans')->nullOnDelete();

            $table->string('payment_gateway')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_subscription_id')->nullable();

            // Copia landlord — no el mismo campo movido, se captura aparte en
            // el wizard (REQ-4). No depende de una consulta cross-DB para el
            // listado del Súper Admin ni para saber a quién avisar si falla un cobro.
            $table->string('business_name')->nullable();
            $table->string('billing_contact_name')->nullable();
            $table->string('billing_contact_email')->nullable();

            $table->boolean('is_demo')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'payment_gateway',
                'gateway_customer_id',
                'gateway_subscription_id',
                'business_name',
                'billing_contact_name',
                'billing_contact_email',
                'is_demo',
            ]);
        });
    }
};
