<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Trazabilidad de sesión/terminal — sin esto es imposible saber después qué
            // Cobros pasaron en un turno dado (Fase 6, REQ-6.6). Nullable: un Cobro
            // registrado desde backoffice no está atado a ninguna terminal física,
            // mismo criterio que Sale::pos_session_id/pos_terminal_id.
            $table->foreignId('pos_session_id')->nullable()->after('created_by')->constrained('pos_sessions');
            $table->foreignId('pos_terminal_id')->nullable()->after('pos_session_id')->constrained('pos_terminals');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_session_id');
            $table->dropConstrainedForeignId('pos_terminal_id');
        });
    }
};
