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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();

            // PDV propietario
            $table->foreignId('point_of_sale_id')
                ->constrained('point_of_sales')
                ->cascadeOnDelete();

            // Tipo de equipo (Freezer, Anaquel, etc.)
            $table->foreignId('equipment_type_id')
                ->constrained('equipment_types')
                ->restrictOnDelete();

            // Identificación del equipo
            $table->string('code', 30)->unique()->nullable();

            // Código interno del sistema: EQ-0001, FRZ-002, etc.

            $table->string('serial_number', 100)->nullable();
            // Serial físico SOLO si aplica

            $table->string('name', 100)->nullable();
            // Nombre descriptivo: "Freezer Entrada", "Anaquel Bebidas"

            $table->string('model', 100)->nullable();

            $table->text('notes')->nullable();

            // Estado
            $table->boolean('active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
