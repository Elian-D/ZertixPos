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
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('journal_entry_id')->nullable()->constrained();
            $table->foreignId('accounting_account_id')->nullable()->constrained('accounting_accounts');
            
            $table->string('document_number')->index(); 
            $table->string('description')->nullable(); // Para saber qué se vendió brevemente
            
            $table->decimal('total_amount', 15, 2);
            $table->decimal('current_balance', 15, 2); 
            
            $table->date('emission_date');
            $table->date('due_date'); 
            
            $table->string('status')->default('unpaid'); // Gestionado por constantes en el Modelo
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
