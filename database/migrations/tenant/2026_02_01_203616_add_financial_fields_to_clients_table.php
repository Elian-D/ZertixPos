<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Límite de crédito y saldo actual
            $table->decimal('credit_limit', 15, 2)->default(0)->after('tax_id');
            $table->decimal('balance', 15, 2)->default(0)->after('credit_limit');
            
            // Días de crédito (Términos de pago)
            $table->integer('payment_terms')->default(0)->after('balance');

            // Relación con el catálogo de cuentas (Solo para clientes grandes)
            $table->foreignId('accounting_account_id')
                ->nullable()
                ->after('payment_terms')
                ->constrained('accounting_accounts')
                ->nullOnDelete();
                
            // Índice para velocidad en reportes financieros
            $table->index('balance');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn(['credit_limit', 'balance', 'payment_terms', 'accounting_account_id']);
        });
    }
};