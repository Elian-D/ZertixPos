<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones Principales
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('receivable_id')->constrained()->onDelete('cascade'); // A qué factura abona
            $table->foreignId('tipo_pago_id')->constrained('tipo_pagos');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');

            // Datos del Recibo
            $table->string('receipt_number')->unique(); // Ej: REC-0001
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference')->nullable(); // No. Cheque, Transferencia o Depósito
            $table->text('note')->nullable();
            
            // Auditoría y Estado
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('active');
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};