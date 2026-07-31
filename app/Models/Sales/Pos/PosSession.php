<?php

namespace App\Models\Sales\Pos;

use App\Models\Configuration\TipoPago;
use App\Models\Sales\Sale;
use App\Models\Sales\SalePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'terminal_id',
        'user_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'status',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'difference',
        'difference_reason',
        'difference_notes',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // --- Constantes de Estado ---
    const STATUS_OPEN   = 'open';
    const STATUS_CLOSED = 'closed';

    // --- Motivos curados de descuadre (Fase 9.3) ---
    const REASON_VUELTO_ERROR        = 'error_vuelto';
    const REASON_CONTEO_ERROR        = 'error_conteo';
    const REASON_VENTA_NO_REGISTRADA = 'venta_no_registrada';
    const REASON_ERROR_COBRO         = 'error_cobro';
    const REASON_BILLETE_FALSO       = 'billete_falso';
    const REASON_GASTO_NO_REGISTRADO = 'gasto_no_registrado';
    const REASON_VUELTO_NO_RECLAMADO = 'vuelto_no_reclamado';
    const REASON_OTRO                = 'otro';

    public static function getReasons(): array
    {
        return [
            self::REASON_VUELTO_ERROR        => 'Error al dar el vuelto',
            self::REASON_CONTEO_ERROR        => 'Error al contar el efectivo',
            self::REASON_VENTA_NO_REGISTRADA => 'Venta cobrada sin registrar en el sistema',
            self::REASON_ERROR_COBRO         => 'Error de cobro (monto incorrecto)',
            self::REASON_BILLETE_FALSO       => 'Billete falso retirado',
            self::REASON_GASTO_NO_REGISTRADO => 'Gasto o retiro de caja no registrado',
            self::REASON_VUELTO_NO_RECLAMADO => 'Vuelto no reclamado por el cliente',
            self::REASON_OTRO                => 'Otro',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN   => 'Abierta',
            self::STATUS_CLOSED => 'Cerrada',
        ];
    }

    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_OPEN   => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            self::STATUS_CLOSED => 'bg-gray-100 text-gray-700 border-gray-200 ring-gray-500/10',
        ];
    }

    public static function getStatusIcons(): array
    {
        return [
            self::STATUS_OPEN   => 'heroicon-s-lock-open',
            self::STATUS_CLOSED => 'heroicon-s-lock-closed',
        ];
    }

    // --- Relaciones ---

    public function terminal(): BelongsTo
    {
        // withTrashed(): una sesión puede seguir existiendo (histórico, arqueo) aunque
        // la terminal a la que perteneció haya sido eliminada (soft delete) después.
        return $this->belongsTo(PosTerminal::class, 'terminal_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Quién abrió el turno. Se mantiene sincronizado con `user_id` (ver PosSessionService::open()).
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    // Quién realmente cerró el turno — puede ser distinto de quien lo abrió
    // (cambio de cajero a mitad de turno). Null mientras el turno sigue abierto.
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class);
    }

        public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'pos_session_id');
    }

    // --- Scopes ---

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    // --- Helpers de Instancia ---

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool {
        return $this->status === self::STATUS_CLOSED;
    }

    // Para usarlo en el modal de cierre antes de que exista el valor en la DB
    public function calculateExpected(): float {
        return app(\App\Services\Sales\Pos\PosSessionServices\PosSessionService::class)->calculateExpected($this);
    }

    // Atributos virtuales para facilitar el arqueo
    public function getCashMovementsInTotalAttribute(): float
    {
        return (float) $this->cashMovements()->where('type', 'in')->sum('amount');
    }

    public function getCashMovementsOutTotalAttribute(): float
    {
        return (float) $this->cashMovements()->where('type', 'out')->sum('amount');
    }

    public function getExpectedCashAttribute(): float
    {
        // Fondo Inicial + Ventas en Efectivo + Entradas Manuales - Salidas Manuales
        return ($this->opening_balance + $this->cash_sales + $this->cash_movements_in_total) - $this->cash_movements_out_total;
    }

    /**
     * Total en efectivo físico que debe haber en la gaveta por ventas de esta sesión.
     *
     * OJO: `Sale->payment_type` es "contado" vs "crédito" (si se cobró ya o quedó a
     * deber), NO el método de pago real — eso vive en `sale_payments.tipo_pago_id`.
     * Sumar por `payment_type = cash` estaba mal en dos formas: (1) una venta de
     * contado pagada por tarjeta/transferencia también tiene `payment_type = cash`
     * y no debía contar aquí, y (2) el Workspace ya soporta pago dividido/mixto
     * (ej. parte en efectivo, parte en tarjeta) — `SaleService::processPayments()`
     * siempre crea una o más filas en `sale_payments` (incluso para pago único, ver
     * el fallback ahí), así que esa tabla es la única fuente confiable de cuánto
     * efectivo físico entró. Por eso aquí se suma `sale_payments.amount` filtrando
     * por `tipoPago->isCash()`, no `sales.total_amount`.
     */
    public function getCashSalesAttribute(): float
    {
        return (float) SalePayment::whereHas('sale', function ($query) {
                $query->where('pos_session_id', $this->id)
                    ->where('status', Sale::STATUS_COMPLETED);
            })
            ->whereHas('tipoPago', function ($query) {
                $query->where('slug', TipoPago::EFECTIVO);
            })
            ->sum('amount');
    }

    // Helper para obtener el neto de movimientos
    public function getNetCashMovementsAttribute()
    {
        $in = $this->cashMovements()->where('type', PosCashMovement::TYPE_IN)->sum('amount');
        $out = $this->cashMovements()->where('type', PosCashMovement::TYPE_OUT)->sum('amount');
        return $in - $out;
    }
}