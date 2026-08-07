<?php

namespace App\Models\Clients;

use App\Enums\TaxIdentifierType;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use App\Models\Configuration\EstadosCliente; // Nueva importación
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use App\Models\Sales\Quotes\Quote;
use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'estado_cliente_id',
        'name',
        'commercial_name',
        'email',
        'phone',
        'provincia_id',
        'municipio_id',
        'address',
        'tax_identifier_type',
        'tax_id',
        // Nuevos campos financieros
        'credit_limit',
        'balance',
        'payment_terms',
        'accounting_account_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_terms' => 'integer',
        'tax_identifier_type' => TaxIdentifierType::class,
    ];

    /* ===========================
     |      RELACIONES
     =========================== */

    public function pos(): HasMany
    {
        return $this->hasMany(PointOfSale::class, 'client_id');
    }

    public function estadoCliente(): BelongsTo
    {
        return $this->belongsTo(EstadosCliente::class, 'estado_cliente_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provincia_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'municipio_id');
    }

    /**
     * Relación con la cuenta contable específica (si aplica)
     */
    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relación con las ventas realizadas por el cliente.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relación con las cotizaciones del cliente.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'customer_id');
    }

    /* ===========================
     |    Mutadores
     =========================== */
    /**
     * Recalcula el saldo actual del cliente basado en sus facturas pendientes.
     */
    public function refreshBalance(): bool
    {
        $this->balance = $this->receivables()
            ->whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
            ->sum('current_balance');

        return $this->save();
    }

    /**
     * Relación con las cuentas por cobrar.
     */
    public function receivables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    /* ===========================
     |    ACCESSORS (AYUDANTES DE VISTA)
     =========================== */

    public function getDisplayNameAttribute(): string
    {
        return $this->commercial_name ?: $this->name;
    }

    public function getTaxLabelAttribute(): string
    {
        return $this->tax_identifier_type?->label() ?? 'ID Fiscal';
    }

    /**
     * Determina si el cliente tiene una cuenta contable propia
     * o si debe usar la cuenta general de CxC.
     */
    public function hasCustomAccount(): bool
    {
        return ! is_null($this->accounting_account_id);
    }

    /* ===========================
    |      SCOPES
    =========================== */

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'estadoCliente:id,nombre,clase_fondo,clase_texto',
            'provincia:id,name',
            'municipio:id,name',
            'accountingAccount:id,code,name', // Añadido a la carga por defecto
        ]);
    }
}
