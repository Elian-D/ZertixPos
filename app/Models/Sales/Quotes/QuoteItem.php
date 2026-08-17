<?php

namespace App\Models\Sales\Quotes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Products\Product;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'quantity',
        'price',
        'discount_amount',
        'discount_percentage',
        'subtotal',
        'tax_amount',
        'tax_breakdown',
    ];

    protected $casts = [
        'tax_breakdown' => 'array',
    ];

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}