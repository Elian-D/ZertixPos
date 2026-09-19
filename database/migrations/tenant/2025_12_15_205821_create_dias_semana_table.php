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
        Schema::create('dias_semana', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique();
            $table->string('codigo', 10)->unique(); // mon, tue, wed
            $table->unsignedTinyInteger('orden')->index();  // 1 - 7
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dias_semana');
    }
};
