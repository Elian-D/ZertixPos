<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.4.0 REQ-3.19 — numeración por tipo de documento (COT, TRN, CXC, FAC).
 * Todo lo de documentos va en esta sola migración. `number` queda nullable a
 * nivel de BD: el relleno de los registros existentes se hace por tinker, y los
 * nuevos los numera el trait HasDocumentNumber al crearse. Las ventas ya tenían
 * document_type_id/number; solo cambian de tipo (FAC → VTA) en el relleno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('id')->constrained('document_types');
            $table->string('number')->nullable()->unique()->after('document_type_id');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('id')->constrained('document_types');
            $table->string('number')->nullable()->unique()->after('document_type_id');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('id')->constrained('document_types');
            // document_number se mantiene como referencia al documento de origen (la venta).
            $table->string('number')->nullable()->unique()->after('document_type_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('id')->constrained('document_types');
        });
    }

    public function down(): void
    {
        foreach (['quotes', 'pos_sessions', 'receivables'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('document_type_id');
                $table->dropUnique(['number']);
                $table->dropColumn('number');
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
        });
    }
};
