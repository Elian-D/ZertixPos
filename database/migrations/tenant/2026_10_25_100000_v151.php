<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        $this->setForeignKeys('cascade');
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
