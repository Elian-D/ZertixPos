<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Nombramos el constraint explícitamente para evitar el error "Duplicate key name '1'"
            $table->foreignId('pos_session_id')
                ->nullable()
                ->constrained('pos_sessions', indexName: 'sales_pos_session_fk')
                ->nullOnDelete();

            $table->foreignId('pos_terminal_id')
                ->nullable()
                ->constrained('pos_terminals', indexName: 'sales_pos_terminal_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign('sales_pos_session_fk');
            $table->dropForeign('sales_pos_terminal_fk');
            $table->dropColumn(['pos_session_id', 'pos_terminal_id']);
        });
    }
};