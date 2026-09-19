<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.1, v1.3.0 Fase 3 — modelo de fechas, no un flag de estado empujado
// por webhook (ver "Por qué un modelo de fechas" en docs/features/v1.3.0.md
// §Fase 3). Tabla landlord, conexión central por defecto.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Sin FK: `plans` todavía vive en la base de cada tenant (REQ-3.4
            // lo mueve a landlord más adelante) — no hay conexión cruzada posible.
            $table->unsignedBigInteger('plan_id');

            $table->string('gateway')->nullable();
            $table->string('gateway_subscription_id')->nullable();

            // Estado crudo reportado por la pasarela — el middleware (REQ-3.5)
            // nunca lo consulta directo, solo current_period_ends_at.
            $table->string('status')->default('trialing');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('current_period_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
