<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 4 — proveedores, CxP, pagos y gastos, en una sola migración:
 * - REQ-4.1: proveedores (catálogo Categoría A, con papelera).
 * - REQ-4.2: CxP genérica (CXP), de una recepción (Fase 5) o de un gasto a crédito.
 * - REQ-4.3: pagos de CxP (EGR) desde una cuenta de Cajas y Bancos.
 * - REQ-4.4: gastos directos, de contado (sale de una cuenta) o a crédito (nace su CxP).
 * - REQ-4.5: `purchases` y `sales.payables` como núcleo flexible, encendidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 150);                 // razón social
            $table->string('trade_name', 150)->nullable();        // nombre comercial
            $table->string('tax_identifier_type', 20)->nullable(); // RNC | CEDULA | PASAPORTE
            $table->string('tax_id', 20)->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('payment_condition', 10)->default('cash'); // cash | credit
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->foreignId('default_financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('rating', 1)->nullable(); // caché A–D, la llena la Fase 5
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tax_id');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('category', 30);
            $table->string('concept', 150);
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('payment_condition', 10); // cash | credit
            // Solo de contado: la cuenta de la que salió el dinero.
            $table->foreignId('financial_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('supplier_invoice', 50)->nullable(); // factura o NCF del proveedor, texto
            $table->text('notes')->nullable();
            $table->string('status', 10)->default('applied'); // applied | voided
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'expense_date']);
            $table->index('category');
        });

        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('vendor_id')->nullable()->constrained()->restrictOnDelete();
            // Origen: Expense (gasto a crédito) o PurchaseReception (Fase 5).
            $table->nullableMorphs('source');
            $table->string('concept', 150);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('balance', 14, 2);
            $table->string('supplier_invoice', 50)->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 10)->default('pending'); // pending | partial | paid | voided
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('payable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('payable_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('reference', 100)->nullable(); // número de transferencia o cheque
            $table->text('notes')->nullable();
            $table->string('status', 10)->default('active'); // active | voided
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_date']);
        });

        foreach ([['Cuenta por pagar', 'CXP'], ['Pago a proveedor', 'EGR']] as [$name, $code]) {
            if (! DB::table('document_types')->where('code', $code)->exists()) {
                DB::table('document_types')->insert([
                    'name' => $name, 'code' => $code, 'prefix' => $code,
                    'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $this->addExpenseNumbers();

        // REQ-4.5: núcleo flexible, encendidos en las instalaciones que ya existen
        // (las nuevas los reciben de InstallationModuleSeeder).
        foreach (['purchases', 'sales.payables'] as $key) {
            if (! DB::table('installation_modules')->where('module_key', $key)->exists()) {
                DB::table('installation_modules')->insert([
                    'module_key' => $key, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Numeración propia de los gastos (GAS-000001). Método aparte para aplicarlo en un
     * tenant que ya corrió el resto (demo): numera los gastos existentes en orden y deja el
     * correlativo donde quedó.
     */
    public function addExpenseNumbers(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('id')->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->nullable()->unique()->after('document_type_id');
        });

        if (! DB::table('document_types')->where('code', 'GAS')->exists()) {
            DB::table('document_types')->insert([
                'name' => 'Gasto', 'code' => 'GAS', 'prefix' => 'GAS',
                'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $type = DB::table('document_types')->where('code', 'GAS')->first();
        $n = (int) $type->current_number;

        foreach (DB::table('expenses')->whereNull('number')->orderBy('id')->pluck('id') as $id) {
            $n++;
            DB::table('expenses')->where('id', $id)->update([
                'document_type_id' => $type->id,
                'number' => 'GAS-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
            ]);
        }

        DB::table('document_types')->where('id', $type->id)->update(['current_number' => $n]);
    }

    public function down(): void
    {
        DB::table('installation_modules')->whereIn('module_key', ['purchases', 'sales.payables'])->delete();
        DB::table('document_types')->whereIn('code', ['CXP', 'EGR', 'GAS'])->delete();

        Schema::dropIfExists('payable_payments');
        Schema::dropIfExists('payables');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('vendors');
    }
};
