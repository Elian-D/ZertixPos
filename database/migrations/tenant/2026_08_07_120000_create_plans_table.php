<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
