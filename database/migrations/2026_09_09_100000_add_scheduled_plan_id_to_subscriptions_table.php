<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-4.7.1, v1.3.0 Fase 4 — downgrade agendado, no aplicado ya. PayPal exige
// reconsentimiento del comprador para CUALQUIER cambio de plan (confirmado
// en vivo contra el sandbox real: reviseSubscription() siempre devuelve un
// link de aprobación, sin importar si el precio nuevo es mayor o menor), así
// que el comprador aprueba el downgrade en el momento — lo que se difiere es
// el efecto sobre `tenants.plan_id` (las funcionalidades), no la aprobación
// en sí. Sin FK a `plans` — mismo criterio que `subscriptions.plan_id`, que
// tampoco la tiene (ver el comentario de esa columna en la migración original).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('scheduled_plan_id')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('scheduled_plan_id');
        });
    }
};
