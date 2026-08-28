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
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->enum('tipo', ['porcentaje', 'fijo']);
            $table->decimal('valor', 8, 2);
            $table->boolean('es_incluido')->default(false);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
