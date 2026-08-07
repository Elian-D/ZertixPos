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
        Schema::create('configuraciones_generales', function (Blueprint $table) {
            $table->id();

            // Datos empresa
            $table->string('nombre_empresa');
            $table->string('logo')->nullable();

            $table->string('tax_id', 50)->nullable();
            $table->string('tax_identifier_type')->nullable();

            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();

            // Ubicación geográfica (RD-only, ver docs/features/v1.1.0.md Fase 6)
            $table->foreignId('provincia_id')->constrained('provinces')->restrictOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipalities')->nullOnDelete();

            $table->timestamps();

            $table->foreignId('impuesto_id')
                ->nullable()
                ->constrained('impuestos')
                ->restrictOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_generales');
    }
};
