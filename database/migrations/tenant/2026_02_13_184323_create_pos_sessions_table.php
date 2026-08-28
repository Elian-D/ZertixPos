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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('terminal_id')->constrained('pos_terminals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            
            // Estados y Tiempos
            $table->string('status')->default('open'); // Usamos constantes en el modelo
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            
            // Contabilidad de Caja
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->nullable();
            
            // Auditoría
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
