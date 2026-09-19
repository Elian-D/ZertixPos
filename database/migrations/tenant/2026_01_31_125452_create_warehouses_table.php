<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            // 'static' (bodegas), 'mobile' (camiones), 'pos' (puntos de venta directos)
            $table->string('type')->default('static'); 
            
            $table->string('address')->nullable(); 
            $table->text('description')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};