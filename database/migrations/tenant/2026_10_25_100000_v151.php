<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v1.5.1 — migración única de la versión (docs/features/v1.5.1.md). Cada fase agrega su
 * propio método.
 *
 * Fase 1 (REQ-1.2): ninguna llave hacia un catálogo borra en cascada. Borrar definitivo una
 * categoría borraba sus productos, y un producto, su kardex. Ahora la base rechaza el
 * borrado y GuardsForceDelete avisa qué lo impide antes de llegar a ella.
 *
 * Siguen en CASCADE las filas que son parte del registro (líneas de un documento, impuestos
 * y existencias del producto, permisos de un rol) y los datos fijos (municipios).
 *
 * Fase 2 (REQ-2.1): subcategorías en dos niveles. `parent_id` null = categoría principal
 * (solo agrupa); con valor = subcategoría (la única que recibe productos). El nombre es
 * único dentro de su padre. Una base con categorías sueltas que ya tienen productos las
 * deja como subcategorías de "General", para que ninguna principal quede con productos.
 */
return new class extends Migration
{
    /** tabla => columnas cuya llave pasa de CASCADE a restrict. */
    private const RESTRICTED = [
        'accounting_accounts' => ['parent_id'],
        'equipments' => ['point_of_sale_id'],
        'inventory_movements' => ['product_id', 'warehouse_id', 'user_id'],
        'payments' => ['client_id'],
        'point_of_sales' => ['client_id'],
        'pos_sessions' => ['terminal_id'],
        'products' => ['category_id', 'unit_id'],
        'receivables' => ['client_id'],
    ];

    public function up(): void
    {
        $this->setForeignKeys('restrict');
        $this->categories();
    }

    public function down(): void
    {
        $this->dropCategories();
        $this->setForeignKeys('cascade');
    }

    private function categories(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->restrictOnDelete();
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
            $table->unique(['parent_id', 'name']);
        });

        $withProducts = DB::table('categories')->whereNull('parent_id')
            ->whereExists(fn ($q) => $q->from('products')->whereColumn('products.category_id', 'categories.id'))
            ->pluck('id');

        if ($withProducts->isEmpty()) {
            return;
        }

        $general = DB::table('categories')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'Agrupa las categorías que había antes de las subcategorías.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('categories')->whereIn('id', $withProducts)->update(['parent_id' => $general]);
    }

    private function dropCategories(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropUnique(['parent_id', 'name']);
            $table->dropColumn('parent_id');
            $table->unique('name');
            $table->unique('slug');
        });
    }

    private function setForeignKeys(string $onDelete): void
    {
        $references = [
            'parent_id' => 'accounting_accounts',
            'point_of_sale_id' => 'point_of_sales',
            'product_id' => 'products',
            'warehouse_id' => 'warehouses',
            'user_id' => 'users',
            'client_id' => 'clients',
            'terminal_id' => 'pos_terminals',
            'category_id' => 'categories',
            'unit_id' => 'units',
        ];

        foreach (self::RESTRICTED as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $references, $onDelete) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                    $blueprint->foreign($column)->references('id')->on($references[$column])->onDelete($onDelete);
                }
            });
        }
    }
};
