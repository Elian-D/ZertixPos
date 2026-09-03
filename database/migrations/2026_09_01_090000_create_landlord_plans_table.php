<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// REQ-3.4, v1.3.0 Fase 3 — `plans`/`plan_module` se mueven de tenant a
// landlord: es catálogo de ZertixPOS (Emprendedor/PyME/Pro), no dato propio
// de cada negocio. Mismo schema que tenía en tenant (ver
// database/migrations/tenant/2026_08_07_120000_create_plans_table.php y
// 2026_08_10_111719_add_features_and_users_limit_to_plans_table.php,
// 2026_08_30_100000_add_gateway_plan_id_to_plans_table.php — consolidados
// acá en una sola migración porque nace de cero en esta conexión).
return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_module');
        Schema::dropIfExists('plans');
    }
};
