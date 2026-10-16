<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 5 — órdenes de compra y recepciones, en una sola migración:
 * - REQ-5.1: orden de compra (OC) y sus líneas.
 * - REQ-5.2: recepción (RCP) y sus líneas: lo recibido, lo rechazado y su motivo.
 * - REQ-5.3: el efecto (stock, costo y CxP) no necesita tablas: usa las de las Fases 1, 3 y 4.
 * - REQ-5.4: la calificación del despacho va en la recepción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('payment_condition', 10); // cash | credit
            // De contado: la cuenta sugerida para pagar al recibir (se puede cambiar ahí).
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            // draft | approved | partial | received | canceled
            $table->string('status', 10)->default('draft');
            $table->timestamp('sent_at')->nullable(); // "Enviada al proveedor": atributo, no estado
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'order_date']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('subtotal', 14, 2);
            // Acumulados de las recepciones: pendiente = cantidad − recibido − rechazado.
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('quantity_rejected', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->date('reception_date');
            $table->string('supplier_invoice', 50)->nullable();
            $table->string('supplier_ncf', 19)->nullable(); // texto, sin validar contra la DGII
            // De contado: la cuenta de la que salió el pago.
            $table->foreignId('financial_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('total', 14, 2)->default(0); // lo recibido × costo; lo rechazado no cuenta
            $table->text('notes')->nullable();
            $table->string('rating', 1)->nullable(); // A–D, opcional (REQ-5.4)
            $table->string('rating_note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('reception_date');
        });

        Schema::create('purchase_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('quantity_rejected', 12, 2)->default(0);
            $table->string('reject_reason', 30)->nullable();
            $table->decimal('unit_cost', 14, 4); // el de la factura; por defecto, el de la orden
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        foreach ([['Orden de compra', 'OC'], ['Recepción de compra', 'RCP']] as [$name, $code]) {
            if (! DB::table('document_types')->where('code', $code)->exists()) {
                DB::table('document_types')->insert([
                    'name' => $name, 'code' => $code, 'prefix' => $code,
                    'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_reception_items');
        Schema::dropIfExists('purchase_receptions');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');

        DB::table('document_types')->whereIn('code', ['OC', 'RCP'])->delete();
    }
};
