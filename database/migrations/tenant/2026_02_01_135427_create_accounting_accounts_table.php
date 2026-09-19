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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('accounting_accounts')->onDelete('cascade');
            
            $table->string('code')->unique(); 
            $table->string('name');
            $table->string('type'); // Cambiado de enum a string
            
            $table->integer('level')->default(1);
            $table->boolean('is_selectable')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
