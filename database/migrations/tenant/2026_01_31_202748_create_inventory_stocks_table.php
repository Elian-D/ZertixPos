<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Cantidades (Usamos decimal para mayor precisión si hay mermas)
            $table->decimal('quantity', 12, 2)->default(0);
            
            // El stock mínimo ahora es por ubicación
            $table->decimal('min_stock', 12, 2)->default(0);

            // Metadatos
            $table->timestamps();

            // ÍNDICE ÚNICO: Vital para que un producto no se duplique en un mismo almacén
            $table->unique(['warehouse_id', 'product_id'], 'product_warehouse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};