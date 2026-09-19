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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ejemplo: Factura de Venta
            $table->string('code', 10)->unique(); // Ejemplo: FAC
            $table->string('prefix', 5)->nullable(); // Ejemplo: F
            $table->integer('current_number')->default(0); // Para el correlativo
            
            // Configuración contable automática (Opcional por ahora, pero útil)
            $table->foreignId('default_debit_account_id')->nullable()->constrained('accounting_accounts');
            $table->foreignId('default_credit_account_id')->nullable()->constrained('accounting_accounts');
            
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
