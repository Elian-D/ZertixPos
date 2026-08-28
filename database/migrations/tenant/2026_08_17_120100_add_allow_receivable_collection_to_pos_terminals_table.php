<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            // Cobrar una deuda ya existente no es una operación de riesgo (a diferencia
            // de un descuento, que sí puede erosionar margen) — default true, se
            // restringe solo en la terminal puntual que lo necesite (Fase 6, REQ-6.5).
            $table->boolean('allow_receivable_collection')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn('allow_receivable_collection');
        });
    }
};
