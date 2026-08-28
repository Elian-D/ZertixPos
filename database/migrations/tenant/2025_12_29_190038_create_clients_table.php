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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Tipo de cliente
            $table->string('type', 20); // individual / company

            // Estado de ciclo de vida — decisión manual, dos valores (Fase 11,
            // REQ-11.3). El estado financiero (moroso) es un cálculo aparte,
            // nunca almacenado — ver Client::esMoroso().
            $table->boolean('is_active')->default(true);

            // Datos legales / contacto
            $table->string('name'); // Nombre del cliente o razón social
            $table->string('commercial_name')->nullable(); // Nombre comercial si aplica
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Ubicación del cliente (RD-only, ver docs/features/v1.1.0.md Fase 6)
            $table->foreignId('provincia_id')->constrained('provinces')->restrictOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('address')->nullable();

            // Identificación fiscal
            $table->string('tax_identifier_type')->nullable();
            $table->string('tax_id', 50)
                ->nullable()
                ->unique();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tax_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
