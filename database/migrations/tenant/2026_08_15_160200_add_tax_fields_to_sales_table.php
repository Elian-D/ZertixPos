<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Base gravable: total_amount (bruto) - discount_total. No se recalcula al
            // vuelo en ningún lado — se persiste como el resto de columnas de montos.
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_total');
            // Σ sale_items.tax_amount (ITBIS + ISC apilados).
            $table->decimal('tax_amount', 15, 2)->default(0)->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'tax_amount']);
        });
    }
};
