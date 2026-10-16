<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.0 Fase 2 — documentos de stock, en una sola migración:
 * - REQ-2.1: toma física (TFS).
 * - REQ-2.2: mermas (MER).
 * - REQ-2.3: devolución dañada → merma (return_items.wasted).
 * - REQ-2.4: transferencias entre almacenes (TRA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            // Alcance: null = todo el almacén; con valor = solo esa categoría.
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('blind')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft'); // draft | applied | canceled
            // Valor de la diferencia al costo congelado (negativo = faltante), al aplicar.
            $table->decimal('difference_value', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // "Existencia al iniciar": lo que decía el sistema al crear la toma, fijo.
            $table->decimal('system_quantity', 12, 2)->default(0);
            // null = sin contar (no genera ajuste).
            $table->decimal('counted_quantity', 12, 2)->nullable();
            // Congelados al aplicar.
            $table->decimal('difference', 12, 2)->nullable();
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_count_id', 'product_id']);
        });

        if (! DB::table('document_types')->where('code', 'TFS')->exists()) {
            DB::table('document_types')->insert([
                'name' => 'Toma física', 'code' => 'TFS', 'prefix' => 'TFS',
                'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->createWastes();
        $this->addReturnWaste();
        $this->createTransfers();
    }

    /**
     * REQ-2.4: transferencias entre almacenes (TRA). Borrador → Enviada (en tránsito)
     * → Recibida; Cancelada solo desde borrador.
     */
    public function createTransfers(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft'); // draft | sent | received | canceled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'from_warehouse_id']);
            $table->index(['status', 'to_warehouse_id']);
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_sent', 12, 2);
            $table->decimal('quantity_received', 12, 2)->nullable(); // al recibir
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_transfer_id', 'product_id']);
        });

        if (! DB::table('document_types')->where('code', 'TRA')->exists()) {
            DB::table('document_types')->insert([
                'name' => 'Transferencia', 'code' => 'TRA', 'prefix' => 'TRA',
                'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * REQ-2.3: una línea de devolución que no regresa a inventario puede ir a merma.
     * `wasted` dice si esa unidad generó la merma de la devolución.
     */
    public function addReturnWaste(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->boolean('wasted')->default(false)->after('restock');
        });
    }

    /**
     * REQ-2.2: mermas (MER). Método aparte para poder aplicarlo solo en un tenant que
     * ya corrió la parte de la toma física (demo), sin revertir la migración entera.
     */
    public function createWastes(): void
    {
        Schema::create('inventory_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('number', 20)->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('waste_date');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('applied'); // applied | voided
            $table->decimal('total_value', 14, 2)->default(0); // valor perdido al costo congelado
            // Origen opcional: una devolución dañada (REQ-2.3) crea su merma.
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status', 'waste_date']);
        });

        Schema::create('inventory_waste_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_waste_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('reason', 30);
            $table->string('notes')->nullable();
            // Congelados al registrar.
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->timestamps();

            $table->index('reason');
        });

        if (! DB::table('document_types')->where('code', 'MER')->exists()) {
            DB::table('document_types')->insert([
                'name' => 'Merma', 'code' => 'MER', 'prefix' => 'MER',
                'current_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
        DB::table('document_types')->where('code', 'TRA')->delete();

        Schema::table('return_items', function (Blueprint $table) {
            $table->dropColumn('wasted');
        });

        Schema::dropIfExists('inventory_waste_items');
        Schema::dropIfExists('inventory_wastes');
        DB::table('document_types')->where('code', 'MER')->delete();

        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        DB::table('document_types')->where('code', 'TFS')->delete();
    }
};
