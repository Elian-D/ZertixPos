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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            // Relación con el tipo de documento para manejar la numeración (FAC, etc)
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('number')->unique(); // Aquí guardaremos el FAC-0001 generado

            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('user_id')->constrained(); // El vendedor
            
            $table->dateTime('sale_date');
            $table->decimal('total_amount', 15, 2);
            
            $table->string('payment_type'); // cash, credit
            $table->decimal('cash_received', 12, 2)->default(0);
            $table->decimal('cash_change', 12, 2)->default(0);
            $table->string('status');       // completed, canceled
            
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
