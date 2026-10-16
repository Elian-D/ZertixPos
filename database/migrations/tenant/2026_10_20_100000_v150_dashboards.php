<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 6 — tableros. La única tabla que cambia es el kardex:
 * `inventory_movements.unit_cost` congela el costo del producto en el momento del
 * movimiento. Sin él, el costo de ventas de un mes pasado cambiaba cada vez que una
 * recepción recalculaba el costo promedio (Fase 5).
 *
 * Los movimientos que ya existen se llenan con el costo actual del producto: no hay otro
 * dato. Los de mermas, tomas físicas y recepciones ya traían el suyo en su línea, así que
 * se toma ese.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 4)->nullable()->after('quantity');
        });

        DB::statement('UPDATE inventory_movements m JOIN products p ON p.id = m.product_id SET m.unit_cost = p.cost');

        DB::statement("UPDATE inventory_movements m
            JOIN inventory_waste_items i ON i.inventory_waste_id = m.reference_id AND i.product_id = m.product_id
            SET m.unit_cost = i.unit_cost
            WHERE m.reference_type = 'App\\\\Models\\\\Inventory\\\\InventoryWaste' AND i.unit_cost IS NOT NULL");

        DB::statement("UPDATE inventory_movements m
            JOIN inventory_count_items i ON i.inventory_count_id = m.reference_id AND i.product_id = m.product_id
            SET m.unit_cost = i.unit_cost
            WHERE m.reference_type = 'App\\\\Models\\\\Inventory\\\\InventoryCount' AND i.unit_cost IS NOT NULL");

        // Una compra entra al costo de su factura, no al promedio que dejó.
        DB::statement("UPDATE inventory_movements m
            JOIN purchase_reception_items i ON i.purchase_reception_id = m.reference_id AND i.product_id = m.product_id
            SET m.unit_cost = i.unit_cost
            WHERE m.reference_type = 'App\\\\Models\\\\Purchases\\\\PurchaseReception'");
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
