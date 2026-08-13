<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropForeign(['default_debit_account_id']);
            $table->dropForeign(['default_credit_account_id']);
            $table->dropColumn(['default_debit_account_id', 'default_credit_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->foreignId('default_debit_account_id')->nullable()->constrained('accounting_accounts');
            $table->foreignId('default_credit_account_id')->nullable()->constrained('accounting_accounts');
        });
    }
};
