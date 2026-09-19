<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_account_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique(); // 'cash_default', 'receivable_default', 'sales_revenue', ...
            $table->foreignId('accounting_account_id')->constrained('accounting_accounts');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_account_roles');
    }
};
