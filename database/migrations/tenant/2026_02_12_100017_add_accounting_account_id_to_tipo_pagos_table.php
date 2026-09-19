<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_pagos', function (Blueprint $table) {
            $table->foreignId('accounting_account_id')
                ->nullable()
                ->after('nombre')
                ->constrained('accounting_accounts');
        });
    }

    public function down(): void
    {
        Schema::table('tipo_pagos', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};
