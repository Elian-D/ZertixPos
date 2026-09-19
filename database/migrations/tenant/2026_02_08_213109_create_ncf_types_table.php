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
        Schema::create('ncf_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ejemplo: Crédito Fiscal
            $table->string('prefix', 1); // B o E
            $table->string('code', 2); // 01, 02, 14, 15
            $table->boolean('is_electronic')->default(false);
            $table->boolean('requires_rnc')->default(false); // Para validar B01
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncf_types');
    }
};
