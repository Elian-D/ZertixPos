<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Agregamos la columna después de 'is_active'
            $table->foreignId('accounting_account_id')
                  ->after('is_active')
                  ->nullable()
                  ->constrained('accounting_accounts')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Primero eliminamos la clave foránea y luego la columna
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};