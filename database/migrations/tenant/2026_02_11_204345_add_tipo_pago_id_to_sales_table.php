<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Se agrega después del payment_type para orden visual
            $table->foreignId('tipo_pago_id')->after('payment_type')->nullable()->constrained('tipo_pagos');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['tipo_pago_id']);
            $table->dropColumn('tipo_pago_id');
        });
    }
};
