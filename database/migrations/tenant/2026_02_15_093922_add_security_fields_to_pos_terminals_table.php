<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            // access_pin guardará el Hash del PIN
            $table->string('access_pin')->nullable()->after('printer_format');
            // Controla si esta terminal específica requiere PIN o es de acceso libre
            $table->boolean('requires_pin')->default(true)->after('access_pin');
        });
    }

    public function down(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn(['access_pin', 'requires_pin']);
        });
    }
};
