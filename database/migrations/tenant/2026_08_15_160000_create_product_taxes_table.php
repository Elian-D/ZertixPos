<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_taxes', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Clave de config('impuestos'), no FK — el catálogo vive en config, no en BD
            // (ver docs/features/v1.2.0.md Fase 5, REQ-5.1).
            $table->string('tax_key');
            $table->primary(['product_id', 'tax_key']);
        });

        // Todo producto existente hereda config('impuestos.default') — antes de esta
        // migración el impuesto era una tasa global única, ningún producto tenía
        // impuestos asignados explícitamente. Sin este backfill, las ventas de
        // productos ya existentes calcularían $0 de impuesto hasta que alguien
        // los editara uno por uno. `default` puede ser null (decisión explícita: en
        // RD no todo lleva ITBIS, no hay tasa que asumir) — sin default, no hay nada
        // que heredar y el backfill no aplica.
        $default = config('impuestos.default');

        if ($default) {
            DB::table('products')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->each(function ($productId) use ($default) {
                    DB::table('product_taxes')->insertOrIgnore([
                        'product_id' => $productId,
                        'tax_key' => $default,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_taxes');
    }
};
