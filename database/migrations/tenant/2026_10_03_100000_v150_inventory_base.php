<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 1 — inventario base, en una sola migración:
 * - REQ-1.2: código de barras del producto.
 * - REQ-1.3: máximo / sobre stock por almacén.
 * - REQ-1.1: las ventas dejan de ser 'output' y sus anulaciones 'adjustment';
 *   los movimientos existentes que referencian una venta pasan a 'sale'/'sale_void'.
 * - REQ-1.6: un servicio no tiene InventoryStock — se borran los que hubiera.
 * - REQ-1.7: products.cost a 4 decimales para que el costo promedio no acumule redondeo.
 */
return new class extends Migration
{
    private const SALE = 'App\\Models\\Sales\\Sale';
    private const SALE_RETURN = 'App\\Models\\Sales\\Returns\\SaleReturn';

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 50)->nullable()->unique()->after('sku');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->decimal('max_stock', 12, 2)->nullable()->after('min_stock');
        });

        DB::table('inventory_movements')
            ->where('reference_type', self::SALE)->where('type', 'output')
            ->update(['type' => 'sale']);

        DB::table('inventory_movements')
            ->where('reference_type', self::SALE)->where('type', 'adjustment')
            ->update(['type' => 'sale_void']);

        // Devoluciones de la primera iteración de v1.4.0 (reemplazo de un cambio como
        // 'output', anulación como 'adjustment'): hoy ReturnService usa siempre 'return'.
        // El down() no las regresa — no hay forma de saber cuál era cuál, y 'return' es correcto.
        DB::table('inventory_movements')
            ->where('reference_type', self::SALE_RETURN)->whereIn('type', ['output', 'adjustment'])
            ->update(['type' => 'return']);

        // REQ-1.6. Los movimientos históricos se conservan; solo se quita la fila de existencia.
        DB::table('inventory_stocks')
            ->whereIn('product_id', DB::table('products')->where('type', 'service')->select('id'))
            ->delete();

        // REQ-1.7.
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 12, 4)->default(0)->change();
        });

    }

    public function down(): void
    {

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 12, 2)->default(0)->change();
        });

        DB::table('inventory_movements')->where('type', 'sale')->update(['type' => 'output']);
        DB::table('inventory_movements')->where('type', 'sale_void')->update(['type' => 'adjustment']);

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropColumn('max_stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
