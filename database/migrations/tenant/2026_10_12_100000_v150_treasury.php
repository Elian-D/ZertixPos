<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 3 — Cajas y Bancos (tesorería), en una sola migración:
 * - REQ-3.1: cuentas y su kardex inmutable.
 * - REQ-3.3: historial de conciliaciones.
 * - REQ-3.6: cuenta de banco de la terminal y por defecto del POS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 10); // cash | bank
            $table->string('bank_name', 100)->nullable();
            $table->string('last_four_digits', 4)->nullable(); // nunca el número completo
            // Caché del kardex: solo lo escribe FinancialMovementService.
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('financial_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained()->restrictOnDelete();
            $table->string('direction', 3); // in | out
            $table->decimal('amount', 14, 2); // siempre positivo
            $table->decimal('balance_after', 14, 2);
            // opening | deposit | withdrawal | transfer | reconciliation | supplier_payment | expense_payment | reversal
            $table->string('origin', 20);
            $table->date('movement_date');
            // Documento que lo originó (conciliación, y en la Fase 4 pagos y gastos).
            $table->nullableMorphs('reference');
            // La otra pata de una transferencia entre cuentas.
            $table->foreignId('counterpart_id')->nullable()->constrained('financial_movements')->nullOnDelete();
            // Reverso: el reverso apunta al original y el original a su reverso.
            $table->foreignId('reverses_id')->nullable()->constrained('financial_movements')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('financial_movements')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['financial_account_id', 'id']);
            $table->index(['financial_account_id', 'movement_date']);
        });

        Schema::create('financial_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained()->restrictOnDelete();
            $table->date('reconciled_at');
            $table->decimal('system_balance', 14, 2); // lo que decía el kardex
            $table->decimal('real_balance', 14, 2);   // lo contado o el estado del banco
            $table->decimal('difference', 14, 2);     // real − sistema
            $table->string('reason', 30)->nullable(); // solo si hay diferencia
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['financial_account_id', 'reconciled_at'], 'fin_reconciliations_account_date_index');
        });

        $this->addPosAccounts();
    }

    /**
     * REQ-3.6: a qué cuenta de banco entran los pagos que no son efectivo. La terminal
     * tiene la suya (opcional) y el POS una por defecto, que usan las terminales sin
     * cuenta propia y lo que se vende o cobra desde el backoffice. Método aparte para
     * aplicarlo solo en un tenant que ya corrió el resto (demo).
     */
    public function addPosAccounts(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->foreignId('financial_account_id')->nullable()->after('cash_account_id')
                ->constrained('financial_accounts')->nullOnDelete();
        });

        Schema::table('pos_settings', function (Blueprint $table) {
            $table->foreignId('default_financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_financial_account_id');
        });

        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_account_id');
        });

        Schema::dropIfExists('financial_reconciliations');
        Schema::dropIfExists('financial_movements');
        Schema::dropIfExists('financial_accounts');
    }
};
