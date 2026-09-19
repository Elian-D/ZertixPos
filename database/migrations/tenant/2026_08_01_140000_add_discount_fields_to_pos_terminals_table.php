<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Los 3 campos de política de descuentos pasan de `pos_settings` (global) a ser
     * obligatorios por terminal — sin fallback al global (ver 11.2 en POS-Interfaz.md).
     * `NOT NULL` con default resuelve el backfill de terminales existentes sin script
     * aparte, con los mismos valores que tenía el global (`PosSetting::createDefault()`).
     */
    public function up(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->boolean('allow_item_discount')->default(true)->after('printer_format');
            $table->boolean('allow_global_discount')->default(true)->after('allow_item_discount');
            $table->decimal('max_discount_percentage', 5, 2)->default(10.00)->after('allow_global_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn(['allow_item_discount', 'allow_global_discount', 'max_discount_percentage']);
        });
    }
};
