<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->decimal('expected_balance', 12, 2)->default(0)->after('opening_balance');
            $table->decimal('difference', 12, 2)->default(0)->after('closing_balance');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn(['expected_balance', 'difference']);
        });
    }
};
