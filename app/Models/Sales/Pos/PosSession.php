<?php

namespace App\Models\Sales\Pos;

use App\Models\Sales\Sale;
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
        'status',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    // --- Constantes de Estado ---
    const STATUS_OPEN   = 'open';
    const STATUS_CLOSED = 'closed';

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
        // Fondo Inicial + Ventas (próximamente) + Entradas Manuales - Salidas Manuales
        $cashSales = $this->cash_sales ?? 0; // Ajustar cuando tengas el módulo de ventas
        return ($this->opening_balance + $cashSales + $this->cash_movements_in_total) - $this->cash_movements_out_total;
    }

    // Helper para obtener el neto de movimientos
    public function getNetCashMovementsAttribute()
    {
        $in = $this->cashMovements()->where('type', PosCashMovement::TYPE_IN)->sum('amount');
        $out = $this->cashMovements()->where('type', PosCashMovement::TYPE_OUT)->sum('amount');
        return $in - $out;
    }
}