<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            // true = Modo Fiscal Activo (Exige NCF y secuencias)
            // false = Modo Documento Interno (Ventas simples)
            $table->boolean('usa_ncf')->default(false)->after('tax_identifier_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_generales', function (Blueprint $table) {
            $table->dropColumn('usa_ncf');
        });
    }
};