<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo y número generado por el sistema
            $table->foreignId('document_type_id')->nullable()->constrained('document_types');
            $table->string('document_number')->nullable()->index(); // Indexado para búsquedas rápidas
            
            $table->date('entry_date'); 
            
            // Referencia para documentos externos (facturas de terceros, # de cheque, NCF)
            $table->string('reference')->nullable(); 
            
            $table->string('description'); 
            $table->string('status');
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
