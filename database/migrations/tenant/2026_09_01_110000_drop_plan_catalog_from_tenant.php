<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.4, v1.3.0 Fase 3 — `plans`/`plan_module` y `configuraciones_generales.plan_id`
// se mueven a landlord (ver 2026_09_01_090000_create_landlord_plans_table.php
// y 2026_09_01_100000_add_billing_fields_to_tenants_table.php, ambas en la
// raíz de migrations). Correr esta migración SOLO después de haber copiado
// los datos reales de `plans`/`plan_module` del tenant a landlord — no hay
// forma de recuperarlos una vez corrida (ver nota en v1.3.0.md §3.4 sobre
// cómo se preservó el `gateway_plan_id` ya sincronizado).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });

        Schema::dropIfExists('plan_module');
        Schema::dropIfExists('plans');
    }

    public function down(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('gateway_plan_id')->nullable();
            $table->json('features')->nullable();
            $table->unsignedTinyInteger('users_limit')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_module', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->primary(['plan_id', 'module_key']);
        });

        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained('plans')->nullOnDelete();
        });
    }
};
