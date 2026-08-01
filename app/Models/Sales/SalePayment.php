<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Configuration\TipoPago;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'tipo_pago_id',
        'pos_session_id',
        'amount',
        'reference',
        'notes'
    ];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function tipoPago(): BelongsTo { return $this->belongsTo(TipoPago::class, 'tipo_pago_id'); }
}