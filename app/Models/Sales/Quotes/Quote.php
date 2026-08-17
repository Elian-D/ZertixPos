<?php

namespace App\Models\Sales\Quotes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Clients\Client;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Sale;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'pos_terminal_id',
        'pos_session_id',
        'sale_id',
        'origin',
        'status',
        'subtotal',
        'discount_total',
        'total',
        'net_amount',
        'tax_amount',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    // Constantes de Origen
    const ORIGIN_BACKOFFICE = 'backoffice';
    const ORIGIN_POS        = 'pos';

    // Constantes de Estado
    const STATUS_DRAFT     = 'draft';
    const STATUS_APPROVED  = 'approved';
    const STATUS_CONVERTED = 'converted';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    public static function getOrigins(): array
    {
        return [
            self::ORIGIN_BACKOFFICE => 'Backoffice',
            self::ORIGIN_POS        => 'POS',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT     => 'Borrador',
            self::STATUS_APPROVED  => 'Aprobada',
            self::STATUS_CONVERTED => 'Convertida',
            self::STATUS_EXPIRED   => 'Vencida',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_DRAFT     => 'bg-gray-100 text-gray-700 border-gray-200',
            self::STATUS_APPROVED  => 'bg-blue-100 text-blue-700 border-blue-200',
            self::STATUS_CONVERTED => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::STATUS_EXPIRED   => 'bg-amber-100 text-amber-700 border-amber-200',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700 border-red-200',
        ];
    }

    /**
     * Total final con impuesto incluido (net_amount + tax_amount) — lo que el
     * cliente realmente paga si aprueba y convierte esta cotización. Mismo
     * patrón que Sale::getGrandTotalAttribute() (Fase 5, REQ-5.12).
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) $this->net_amount + (float) $this->tax_amount;
    }

    // Relaciones
    public function items(): HasMany { return $this->hasMany(QuoteItem::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Client::class, 'customer_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(PosTerminal::class, 'pos_terminal_id'); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}