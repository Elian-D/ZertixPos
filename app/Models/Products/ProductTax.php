<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Fila de la tabla pivote `product_taxes` (product_id, tax_key) — sin
 * modelo hasta ahora, Product::taxes() consultaba con DB::table() directo
 * por fila, generando N+1 en cualquier listado que muestre price_with_tax
 * (ver ProductTable::filterMap() / Product::scopeWithIndexRelations()).
 */
class ProductTax extends Model
{
    public $timestamps = false;

    protected $table = 'product_taxes';

    protected $fillable = ['product_id', 'tax_key'];
}
