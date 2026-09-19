<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dias_semana');
    }

    public function down(): void
    {
        Schema::create('dias_semana', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique();
            $table->string('codigo', 10)->unique();
            $table->unsignedTinyInteger('orden')->index();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }
};
