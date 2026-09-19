<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            
            // Origen y Producto
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Para transferencias (Opcional si no es transfer)
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            // Datos del movimiento
            $table->decimal('quantity', 12, 2); 
            $table->string('type'); // input (entrada), output (salida), adjustment (ajuste), transfer (traslado)
            $table->decimal('previous_stock', 12, 2)->default(0); // Auditoría: Stock antes del cambio
            $table->decimal('current_stock', 12, 2)->default(0);  // Auditoría: Stock después del cambio
            
            $table->string('description')->nullable(); 
            
            // Referencia Polimórfica (Ventas, Compras, Producción)
            $table->nullableMorphs('reference'); 

            $table->softDeletes(); // Requerido por tu checklist
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};