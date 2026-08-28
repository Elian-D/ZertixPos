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
        Schema::create('ncf_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ncf_type_id')->constrained('ncf_types');
            $table->string('series', 1)->default('B'); 
            $table->unsignedBigInteger('from');
            $table->unsignedBigInteger('to');
            $table->unsignedBigInteger('current');
            $table->date('expiry_date');
            $table->integer('alert_threshold')->default(50);
            $table->string('status'); // Constantes: ACTIVE, EXHAUSTED, EXPIRED
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncf_sequences');
    }
};
