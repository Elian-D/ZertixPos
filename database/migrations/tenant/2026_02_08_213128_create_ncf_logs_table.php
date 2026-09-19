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
        Schema::create('ncf_logs', function (Blueprint $table) {
            $table->id();
            $table->string('full_ncf')->unique(); // El número completo formateado
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('ncf_type_id')->constrained('ncf_types');
            $table->foreignId('ncf_sequence_id')->nullable()->constrained('ncf_sequences');
            $table->string('status'); // Constantes: USED, VOIDED
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('user_id')->constrained('users'); // Quién lo emitió/anuló
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncf_logs');
    }
};
