<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Cuenta de Cajas y Bancos (v1.5.0 REQ-3.1): una caja física ("Caja fuerte") o una
 * cuenta de banco. El saldo es la caché de su kardex (FinancialMovement) y solo lo
 * escribe FinancialMovementService.
 *
 * Sin SoftDeletes: una cuenta con movimientos no se borra, se desactiva.
 */
class FinancialAccount extends Model
{
    const TYPE_CASH = 'cash';
    const TYPE_BANK = 'bank';

    protected $fillable = [
        'name', 'type', 'bank_name', 'last_four_digits', 'notes', 'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function getTypes(): array
    {
        return [
            self::TYPE_CASH => 'Caja',
            self::TYPE_BANK => 'Banco',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type === self::TYPE_BANK ? 'heroicon-s-building-library' : 'heroicon-s-banknotes';
    }

    public function isBank(): bool
    {
        return $this->type === self::TYPE_BANK;
    }

    /** "Banco Popular · ****1234", o null si es una caja o no tiene datos. */
    public function getBankLabelAttribute(): ?string
    {
        if (! $this->isBank()) {
            return null;
        }

        $parts = array_filter([$this->bank_name, $this->last_four_digits ? '****'.$this->last_four_digits : null]);

        return $parts ? implode(' · ', $parts) : null;
    }

    public function isNegative(): bool
    {
        return (float) $this->current_balance < 0;
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with('lastReconciliation');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinancialMovement::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(FinancialReconciliation::class);
    }

    public function lastReconciliation(): HasOne
    {
        return $this->hasOne(FinancialReconciliation::class)->latestOfMany();
    }
}
