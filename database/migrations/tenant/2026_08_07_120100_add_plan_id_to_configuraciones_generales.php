<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
