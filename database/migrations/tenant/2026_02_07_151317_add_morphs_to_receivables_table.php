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
        Schema::table('receivables', function (Blueprint $table) {
            // Esto añade reference_type y reference_id después de accounting_account_id
            $table->nullableMorphs('reference'); 
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            //
        });
    }
};
