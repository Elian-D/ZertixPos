<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->foreignId('opened_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->after('opened_by_user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_user_id');
            $table->dropConstrainedForeignId('opened_by_user_id');
        });
    }
};
