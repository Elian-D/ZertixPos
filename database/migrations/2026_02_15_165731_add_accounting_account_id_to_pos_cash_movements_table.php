<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_cash_movements', function (Blueprint $table) {
            // La hacemos obligatoria porque cada centavo que se mueve debe saber a dónde va
            $table->foreignId('accounting_account_id')
                ->after('user_id')
                ->constrained('accounting_accounts')
                ->onDelete('restrict'); 
        });
    }

    public function down(): void
    {
        Schema::table('pos_cash_movements', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};
