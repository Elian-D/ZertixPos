<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.4.0 Fase 1, REQ-1.1 — reemplaza el booleano `is_stockable` por un campo
 * `type` explícito. String normal, no `enum` de MySQL a propósito (convención
 * del proyecto: los tipos se gobiernan con constantes en el modelo, ej.
 * InventoryMovement::TYPE_*, Sale::STATUS_* — un `enum` de columna exige una
 * migración de schema para agregar un valor nuevo el día que exista, por
 * ejemplo, un tipo "combo"; un string validado por Rule::in() no). Se agrega
 * y se rellena acá; la columna vieja se elimina en la migración siguiente,
 * una vez que todo el código que la consumía ya lea `type` (ver
 * docs/features/v1.4.0.md §1.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->default('product')->after('is_stockable');
        });

        DB::table('products')->where('is_stockable', false)->update(['type' => 'service']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
