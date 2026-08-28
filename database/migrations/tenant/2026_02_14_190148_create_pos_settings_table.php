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
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            
            // Descuentos
            $table->boolean('allow_item_discount')->default(true);
            $table->boolean('allow_global_discount')->default(true);
            $table->decimal('max_discount_percentage', 5, 2)->default(10.00);
            
            // Clientes
            $table->boolean('allow_quick_customer_creation')->default(true);
            $table->foreignId('default_walkin_customer_id')
                ->nullable()
                ->constrained('clients')
                ->onDelete('set null');
            
            // Cotizaciones y Flujo
            $table->boolean('allow_quote_without_save')->default(true);
            $table->boolean('auto_print_receipt')->default(true);
            $table->string('receipt_size')->default('80mm'); // 58mm o 80mm
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
    }
};
