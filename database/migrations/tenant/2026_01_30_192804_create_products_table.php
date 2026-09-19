<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                    ->constrained('categories')
                    ->cascadeOnDelete();

            $table->foreignId('unit_id')
                    ->constrained('units')
                    ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Contabilidad y Precios
            // Nota: El precio y costo son globales o base.
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);

            // Flags
            $table->boolean('is_active')->default(true);
            
            // Este flag es vital: determina si el producto genera registros en inventory_stocks
            $table->boolean('is_stockable')->default(true);

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};