<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
            // Snapshot histórico: [{key, label, rate, amount}, ...] — mismo patrón que
            // sale_items.tax_breakdown (Fase 5, REQ-5.2/5.12).
            $table->json('tax_breakdown')->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_breakdown']);
        });
    }
};
