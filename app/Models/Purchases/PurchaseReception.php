<?php

namespace App\Models\Purchases;

use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Recepción de compra (v1.5.0 REQ-5.2, RCP): la bitácora de cada entrega de una orden.
 * Al guardarla entra el stock, se recalcula el costo promedio y nace su CxP (pagada al
 * instante si la orden es de contado). Lo rechazado no entra ni se paga: cierra pendiente.
 *
 * No se edita ni se anula: un error se corrige con una toma física o una merma, más un
 * movimiento de tesorería.
 */
class PurchaseReception extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'RCP';

    /** Calificación del despacho (REQ-5.4) y su peso para el promedio del proveedor. */
    const RATINGS = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];

    protected $fillable = [
        'purchase_order_id', 'reception_date', 'supplier_invoice', 'supplier_ncf',
        'financial_account_id', 'total', 'notes', 'rating', 'rating_note', 'created_by',
    ];

    protected $casts = [
        'reception_date' => 'date',
        'total' => 'decimal:2',
    ];

    public static function getRatingLabels(): array
    {
        return [
            'A' => 'Excelente',
            'B' => 'Bueno',
            'C' => 'Regular',
            'D' => 'Malo',
        ];
    }

    public function getRatingVariantAttribute(): string
    {
        return match ($this->rating) {
            'A' => 'success',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'error',
            default => 'slate',
        };
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'order:id,number,vendor_id,warehouse_id,payment_condition',
            'order.vendor:id,business_name,trade_name',
            'order.warehouse:id,name',
            'payable:id,source_type,source_id,number,status,balance,due_date',
        ])->withSum('items as quantity_received_total', 'quantity_received')
            ->withSum('items as quantity_rejected_total', 'quantity_rejected');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceptionItem::class);
    }

    /** De contado: la cuenta de la que salió el pago. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /** Lo que se debe (o se pagó de contado) por esta entrega. Sin CxP si todo se rechazó. */
    public function payable(): MorphOne
    {
        return $this->morphOne(Payable::class, 'source');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
