<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.4.0 REQ-3.18 — la anulación se registra en la propia venta. Antes el motivo
 * solo vivía en ncf_logs, así que una venta sin NCF lo perdía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->foreignId('canceled_by')->nullable()->after('cancellation_reason')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('canceled_at')->nullable()->after('canceled_by');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canceled_by');
            $table->dropColumn(['cancellation_reason', 'canceled_at']);
        });
    }
};
