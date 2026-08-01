<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La política de descuentos deja de ser global (ver migración hermana
     * add_discount_fields_to_pos_terminals_table y 11.2 en POS-Interfaz.md).
     * El resto de `pos_settings` (cliente rápido, cotizaciones, auto-print,
     * receipt_size) sigue siendo config global legítima, no se toca.
     */
    public function up(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn(['allow_item_discount', 'allow_global_discount', 'max_discount_percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->boolean('allow_item_discount')->default(true);
            $table->boolean('allow_global_discount')->default(true);
            $table->decimal('max_discount_percentage', 5, 2)->default(10.00);
        });
    }
};
