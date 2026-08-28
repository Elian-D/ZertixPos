<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El modelo Impuesto era una tasa global única (1:1 con ConfiguracionGeneral),
        // reemplazado por config('impuestos') + el pivote product_taxes (multi-tasa
        // por producto, ver docs/features/v1.2.0.md Fase 5, REQ-5.1).
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('impuesto_id');
        });

        Schema::dropIfExists('impuestos');
    }

    public function down(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', ['porcentaje', 'fijo']);
            $table->decimal('valor', 8, 2);
            $table->boolean('es_incluido')->default(false);
            $table->timestamps();
        });

        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->foreignId('impuesto_id')->nullable()->constrained('impuestos')->restrictOnDelete();
        });
    }
};
