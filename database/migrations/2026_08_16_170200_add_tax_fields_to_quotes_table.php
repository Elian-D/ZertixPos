<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // net_amount = Σ quote_items.subtotal (ya post-descuento por línea).
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_total');
            // Σ quote_items.tax_amount (ITBIS + ISC apilados) — mismo patrón que
            // sales.tax_amount (Fase 5, REQ-5.2/5.12).
            $table->decimal('tax_amount', 15, 2)->default(0)->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'tax_amount']);
        });
    }
};
