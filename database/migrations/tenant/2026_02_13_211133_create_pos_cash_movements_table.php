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
        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_session_id')->constrained('pos_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('accounting_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            
            $table->string('type'); // 'in' o 'out'
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_cash_movements');
    }
};
