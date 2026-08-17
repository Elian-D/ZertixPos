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
    // withTrashed() (REQ-2.5): un pago histórico debe seguir mostrando su método
    // de pago aunque ese TipoPago se haya desactivado/borrado después — sin esto,
    // la relación se resuelve a null y el pago pierde su método en pantalla.
    public function tipoPago(): BelongsTo { return $this->belongsTo(TipoPago::class, 'tipo_pago_id')->withTrashed(); }
}