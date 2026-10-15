<?php

namespace App\Models\Purchases;

use App\Enums\TaxIdentifierType;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Proveedor (v1.5.0 REQ-4.1). Catálogo Categoría A (docs/analisis/politica-soft-deletes.md):
 * se borra a la papelera y se restaura; el borrado definitivo solo si no tiene CxP, gastos
 * ni órdenes de compra.
 *
 * La calificación (`rating`, A–D) no se escribe a mano: es la caché del promedio de los
 * despachos calificados al recibir (REQ-5.4, PurchaseReceptionService). Sin calificaciones,
 * "Sin calificar".
 */
class Vendor extends Model
{
    use SoftDeletes;

    const CONDITION_CASH = 'cash';
    const CONDITION_CREDIT = 'credit';

    protected $fillable = [
        'business_name', 'trade_name', 'tax_identifier_type', 'tax_id',
        'contact_name', 'phone', 'email', 'address',
        'payment_condition', 'credit_days', 'default_financial_account_id', 'notes', 'is_active',
    ];

    protected $casts = [
        'credit_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function getConditions(): array
    {
        return [
            self::CONDITION_CASH => 'Contado',
            self::CONDITION_CREDIT => 'Crédito',
        ];
    }

    /** Nombre para mostrar: el comercial si lo tiene, si no la razón social. */
    public function getDisplayNameAttribute(): string
    {
        return $this->trade_name ?: $this->business_name;
    }

    public function getConditionLabelAttribute(): string
    {
        return self::getConditions()[$this->payment_condition] ?? $this->payment_condition;
    }

    public function getTaxIdentifierLabelAttribute(): ?string
    {
        return $this->tax_identifier_type ? (TaxIdentifierType::tryFrom($this->tax_identifier_type)?->label() ?? $this->tax_identifier_type) : null;
    }

    public function getRatingLabelAttribute(): string
    {
        return $this->rating ?: 'Sin calificar';
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
        // Saldo pendiente sin N+1 en el listado.
        return $query->withSum(['payables as pending_balance' => fn ($q) => $q->whereIn('status', [Payable::STATUS_PENDING, Payable::STATUS_PARTIAL])], 'balance');
    }

    public function defaultAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'default_financial_account_id');
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function receptions(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseReception::class, PurchaseOrder::class);
    }

    /** Si ya tiene documentos: entonces no se borra definitivo (la papelera sí). */
    public function hasDocuments(): bool
    {
        return $this->payables()->exists() || $this->expenses()->exists() || $this->purchaseOrders()->exists();
    }
}
