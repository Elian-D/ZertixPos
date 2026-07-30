<?php

namespace App\Models\Sales;

use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Ncf\NcfLog;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Quotes\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_type_id',
        'number',
        'client_id',
        'warehouse_id',
        'user_id',
        'sale_date',
        'total_amount',
        'discount_total',
        'payment_type',
        'tipo_pago_id',
        'cash_received',
        'cash_change',
        'status',
        'notes',
        // NUEVOS CAMPOS POS
        'pos_session_id',
        'pos_terminal_id',
        'sale_origin',
        'is_walkin_customer',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'is_walkin_customer' => 'boolean',
    ];

    // Constantes de Estado
    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELED = 'canceled';

    // Constantes de Tipo de Pago
    const PAYMENT_CASH = 'cash';

    const PAYMENT_CREDIT = 'credit';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELED => 'Anulada',
        ];
    }

    public static function getPaymentTypes(): array
    {
        return [
            self::PAYMENT_CASH => 'Contado',
            self::PAYMENT_CREDIT => 'Crédito',
        ];
    }

    // Estilos para los Estados (Status)
    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            self::STATUS_CANCELED => 'bg-red-100 text-red-700 border-red-200 ring-red-500/10',
        ];
    }

    // NUEVO: Estilos para Tipos de Pago (Badges)
    public static function getPaymentTypeStyles(): array
    {
        return [
            self::PAYMENT_CASH => 'bg-blue-100 text-blue-700 border-blue-200 ring-blue-500/10',
            self::PAYMENT_CREDIT => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500/10',
        ];
    }

    // NUEVO: Iconos para Tipos de Pago (Heroicons)
    public static function getPaymentTypeIcons(): array
    {
        return [
            self::PAYMENT_CASH => 'heroicon-s-banknotes',
            self::PAYMENT_CREDIT => 'heroicon-s-credit-card',
        ];
    }

    /**
     * Centraliza las relaciones necesarias para el Index y Exportaciones
     */
    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'client:id,name,tax_id',
            'user:id,name',
            'warehouse:id,name',
            'tipoPago:id,nombre',
            'posSession',    // <--- NUEVO
            'posTerminal',   // <--- NUEVO
            'payments.tipoPago', // <--- NUEVO (Para ver los métodos de pago usados)
            'items', // Cargamos todos los campos de los items (precio, cantidad)
            'items.product:id,name,sku', // Cargamos el producto de cada item
            'quote:id,discount_total', // <--- NUEVO (Para mostrar descuentos de cotizacion)
        ]);
    }

    public function requiresNcf(): bool
    {
        $config = general_config();

        // Si el sistema no usa NCF, nada lo requiere.
        if (! $config?->usa_ncf) {
            return false;
        }

        // Si el cliente NO es Consumidor Final (ID != 1) o tiene RNC, requiere NCF fiscal.
        if ($this->client_id != 1 || ! empty($this->client?->tax_id)) {
            return true;
        }

        return false;
    }

    /**
     * Accesor opcional para obtener el número de NCF directamente
     * sin tener que escribir $sale->ncfLog->full_ncf siempre.
     */
    public function getNcfAttribute()
    {
        return $this->ncfLog?->full_ncf;
    }

    /**
     * Accesor para saber si la venta se originó en el POS
     */
    public function getIsPosSaleAttribute(): bool
    {
        return ! is_null($this->pos_session_id);
    }

    /**
     * Relación con la sesión de POS
     */
    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    /**
     * Relación con la terminal de POS
     */
    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    /**
     * Relación con la cotización (si se originó de una)
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'id', 'sale_id');
    }

    /**
     * Relación con el detalle de pagos (Multipay)
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    // Relaciones
    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    } // NUEVA

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function ncfLog(): HasOne
    {
        return $this->hasOne(NcfLog::class, 'sale_id');
    }
}
