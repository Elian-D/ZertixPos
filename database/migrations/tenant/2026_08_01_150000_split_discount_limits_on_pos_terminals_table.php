<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 11.2.5: un solo `max_discount_percentage` no alcanza — el motor de ventas necesita
     * distinguir el tope de descuento por ítem del tope de descuento global para poder
     * validar cada uno contra la señal correcta (`discount_percentage` del ítem vs. el
     * remanente repartido bajo la Regla de Exclusión). Ver docs/features/POS-Interfaz.md.
     *
     * `discount_policy` queda fijo en 'exclusion' (única política operativa por ahora,
     * sin selector en la UI) — el valor 'cascade' está reservado para una política
     * alternativa de reparto documentada pero no implementada.
     */
    public function up(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->decimal('max_item_discount_percentage', 5, 2)->default(5.00)->after('max_discount_percentage');
            $table->decimal('max_global_discount_percentage', 5, 2)->default(10.00)->after('max_item_discount_percentage');
            $table->string('discount_policy', 20)->default('exclusion')->after('max_global_discount_percentage');
        });

        // Backfill: preservar el valor que cada terminal ya tenía como tope único,
        // aplicado a ambos campos nuevos, en vez de resetear todas a los defaults.
        DB::table('pos_terminals')->update([
            'max_item_discount_percentage' => DB::raw('max_discount_percentage'),
            'max_global_discount_percentage' => DB::raw('max_discount_percentage'),
        ]);

        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn('max_discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->decimal('max_discount_percentage', 5, 2)->default(10.00);
        });

        DB::table('pos_terminals')->update([
            'max_discount_percentage' => DB::raw('max_item_discount_percentage'),
        ]);

        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn(['max_item_discount_percentage', 'max_global_discount_percentage', 'discount_policy']);
        });
    }
};
