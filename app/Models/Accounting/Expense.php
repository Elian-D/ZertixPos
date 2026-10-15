<?php

namespace App\Models\Accounting;

use App\Models\Purchases\Vendor;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Gasto directo (v1.5.0 REQ-4.4): qué se consumió (luz, alquiler, gasolina…), separado de
 * a quién se le debe.
 * - De contado: el dinero sale de una cuenta de Cajas y Bancos al registrarlo. No hay CxP.
 * - A crédito: nace su CxP (Payable con source = este gasto) y se paga después (EGR).
 *
 * Numerado (GAS-000001). Nunca se edita ni se borra; anularlo devuelve el dinero a la
 * cuenta (contado) o anula su CxP con sus pagos (crédito). Ciclo de vida propio: sin SoftDeletes.
 */
class Expense extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'GAS';

    const STATUS_APPLIED = 'applied';
    const STATUS_VOIDED = 'voided';

    const CONDITION_CASH = 'cash';
    const CONDITION_CREDIT = 'credit';

    // Categorías alineadas a los conceptos que pide la DGII (base para el 606 y para el
    // estado de resultados de la Fase 6). Lista fija, sin catálogo editable.
    const CATEGORY_PERSONAL = 'personal_expenses';
    const CATEGORY_BASIC_SERVICES = 'basic_services';
    const CATEGORY_RENT = 'rent';
    const CATEGORY_TRANSPORT = 'transport';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_SUPPLIES = 'supplies';
    const CATEGORY_TAXES = 'taxes_licenses';
    const CATEGORY_OTHER = 'other_expenses';

    protected $fillable = [
        'vendor_id', 'category', 'concept', 'amount', 'expense_date', 'payment_condition',
        'financial_account_id', 'supplier_invoice', 'notes', 'status',
        'created_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'voided_at' => 'datetime',
    ];

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_PERSONAL => 'Gastos de personal / nómina',
            self::CATEGORY_BASIC_SERVICES => 'Servicios básicos',
            self::CATEGORY_RENT => 'Alquiler',
            self::CATEGORY_TRANSPORT => 'Transporte / combustible',
            self::CATEGORY_MAINTENANCE => 'Reparaciones y mantenimiento',
            self::CATEGORY_SUPPLIES => 'Materiales y suministros',
            self::CATEGORY_TAXES => 'Impuestos, tasas y permisos',
            self::CATEGORY_OTHER => 'Otros gastos',
        ];
    }

    /** Ejemplos para el hint del formulario: qué va en cada categoría. */
    public static function getCategoryHints(): array
    {
        return [
            self::CATEGORY_PERSONAL => 'Sueldos, TSS, regalía pascual, incentivos.',
            self::CATEGORY_BASIC_SERVICES => 'Luz, agua, internet, teléfono.',
            self::CATEGORY_RENT => 'Local comercial o almacén.',
            self::CATEGORY_TRANSPORT => 'Gasolina del delivery, fletes, pasajes.',
            self::CATEGORY_MAINTENANCE => 'Arreglos del local, freezer, planta eléctrica.',
            self::CATEGORY_SUPPLIES => 'Fundas, papel térmico, limpieza.',
            self::CATEGORY_TAXES => 'Marbete, basura del ayuntamiento, registros.',
            self::CATEGORY_OTHER => 'Lo que no encaje arriba.',
        ];
    }

    public static function getConditions(): array
    {
        return [
            self::CONDITION_CASH => 'Contado',
            self::CONDITION_CREDIT => 'Crédito',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_APPLIED => 'Registrado',
            self::STATUS_VOIDED => 'Anulado',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    public function getConditionLabelAttribute(): string
    {
        return self::getConditions()[$this->payment_condition] ?? $this->payment_condition;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return $this->isVoided() ? 'error' : 'success';
    }

    public function isCash(): bool
    {
        return $this->payment_condition === self::CONDITION_CASH;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['vendor:id,business_name,trade_name', 'account:id,name', 'payable:id,source_type,source_id,number,balance,status,due_date']);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /** De contado: la cuenta de la que salió el dinero. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /** A crédito: su cuenta por pagar. */
    public function payable(): MorphOne
    {
        return $this->morphOne(Payable::class, 'source');
    }

    /** De contado: el movimiento que sacó el dinero de la cuenta (su reverso no lleva referencia). */
    public function movement(): MorphOne
    {
        return $this->morphOne(FinancialMovement::class, 'reference');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
