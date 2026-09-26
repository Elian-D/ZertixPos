<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.4.0 Fase 1, REQ-1.1 — segunda mitad del rename. Todo el código que leía
 * `is_stockable` ya fue migrado a `Product::TYPE_PRODUCT`/`TYPE_SERVICE` en
 * esta misma versión (ver docs/features/v1.4.0.md §1.1) — la columna vieja
 * no convive indefinidamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_stockable');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_stockable')->default(true)->after('cost');
        });
    }
};
