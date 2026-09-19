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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('clients');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('pos_terminal_id')->nullable()->constrained('pos_terminals');
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions');
            $table->foreignId('sale_id')->nullable()->constrained('sales');
            
            $table->string('origin')->default('backoffice'); // backoffice, pos
            $table->string('status')->default('draft'); // draft, approved, converted, expired, cancelled
            
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
