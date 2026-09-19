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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Relación con la Venta (El origen)
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            
            // Identificación Legal
            $table->string('invoice_number')->unique(); // Correlativo interno o NCF
            $table->string('type'); // 'contado', 'credito' (Redundante pero útil para reportes rápidos)
            
            // Formato de impresión preferido
            $table->string('format_type')->default('ticket'); // 'ticket', 'letter', 'route'
            
            // Estados (Como string para constantes en el Modelo)
            $table->string('status')->default('active'); // 'active', 'cancelled', 'refunded'
            
            // Información de fechas legales
            $table->date('due_date')->nullable(); // Para facturas a crédito
            
            // Firmas y Metadatos
            $table->string('generated_by')->nullable(); // Usuario que emitió
            $table->text('digital_signature')->nullable(); // Por si integras facturación electrónica luego
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
