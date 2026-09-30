<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.4.0 Fase 2, REQ-2.1 — Devoluciones y Cambios (ver docs/features/v1.4.0.md §2.1).
 * Sin SoftDeletes: Categoría C (status propio, anular = `voided`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('number')->unique();
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('user_id')->constrained('users');
            $table->string('reason');
            $table->string('refund_method'); // cash | exchange | receivable
            $table->decimal('refund_value', 15, 2);
            $table->decimal('cash_amount', 15, 2)->nullable();
            $table->foreignId('exchange_sale_id')->nullable()->constrained('sales');
            $table->text('notes')->nullable();
            $table->string('status')->default('completed'); // completed | voided
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'status']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items');
            $table->decimal('quantity', 12, 2);
            // 4 decimales: subtotal/quantity de la línea original no siempre es exacto a 2.
            $table->decimal('unit_subtotal', 15, 4);
            $table->decimal('unit_tax', 15, 4)->default(0);
            $table->boolean('restock')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};
