<?php

namespace App\Models\Clients;

use App\Models\Configuration\EstadosCliente;
use App\Models\Geo\State;
use App\Models\Accounting\AccountingAccount; // Nueva importación
use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Configuration\ConfiguracionGeneral;
use App\Models\Configuration\TaxIdentifierType;
use App\Models\Sales\Quotes\Quote;
use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'state_id',
        'city',
        'address',
        'tax_identifier_type_id',
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function taxIdentifierType(): BelongsTo
    {
        return $this->belongsTo(TaxIdentifierType::class, 'tax_identifier_type_id');
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
        if ($this->taxIdentifierType) {
            return $this->taxIdentifierType->code ?? $this->taxIdentifierType->name;
        }

        $config = general_config();
        if (!$config) return 'ID Fiscal';

        $entityType = $this->type === 'individual' ? 'person' : 'company';
        
        $default = TaxIdentifierType::where('country_id', $config->country_id)
                    ->whereIn('entity_type', [$entityType, 'both'])
                    ->first();

        return $default?->code ?? 'ID Fiscal';
    }

    /**
     * Determina si el cliente tiene una cuenta contable propia 
     * o si debe usar la cuenta general de CxC.
     */
    public function hasCustomAccount(): bool
    {
        return !is_null($this->accounting_account_id);
    }

    /* ===========================
    |      SCOPES
    =========================== */

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'estadoCliente:id,nombre,clase_fondo,clase_texto',
            'state:id,name',
            'taxIdentifierType:id,name,code',
            'accountingAccount:id,code,name', // Añadido a la carga por defecto
        ]);
    }
}