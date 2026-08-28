<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('tipo_pagos', function (Blueprint $table) {
            // Es mejor que no sea nullable si será unique, 
            // pero si ya hay datos, se deja nullable primero y luego se llena.
            $table->string('slug')->unique()->after('nombre')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tipo_pagos', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};