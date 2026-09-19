<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('tipo_pago_id')->constrained('tipo_pagos');
            // Añadimos nombre al constraint para evitar conflictos en el POS
            $table->foreignId('pos_session_id')
                ->nullable()
                ->constrained('pos_sessions', indexName: 'payments_pos_session_fk');
            
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable(); 
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};