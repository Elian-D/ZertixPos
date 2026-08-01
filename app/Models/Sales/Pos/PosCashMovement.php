<?php

namespace App\Models\Sales\Pos;

use App\Models\User;
use App\Models\Accounting\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosCashMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pos_session_id',
        'user_id',
        'accounting_account_id',
        'accounting_entry_id',
        'type',
        'amount',
        'reason',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'metadata' => 'array',
    ];

    // --- Constantes de Tipo ---
    const TYPE_IN  = 'in';
    const TYPE_OUT = 'out';

    public static function getTypes(): array
    {
        return [
            self::TYPE_IN  => 'Entrada de Efectivo',
            self::TYPE_OUT => 'Salida de Efectivo',
        ];
    }

    public static function getTypeStyles(): array
    {
        return [
            self::TYPE_IN  => 'bg-green-100 text-green-700 border-green-200',
            self::TYPE_OUT => 'bg-amber-100 text-amber-700 border-amber-200',
        ];
    }

    public static function getTypeIcons(): array
    {
        return [
            self::TYPE_IN  => 'heroicon-s-arrow-trending-up',
            self::TYPE_OUT => 'heroicon-s-arrow-trending-down',
        ];
    }

    // --- Relaciones ---

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'accounting_entry_id');
    }

    // --- Nueva Relación ---
    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\AccountingAccount::class, 'accounting_account_id');
    }

    // --- Scopes ---

    public function scopeIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    // --- Helpers ---

    public function isEntry(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isExit(): bool
    {
        return $this->type === self::TYPE_OUT;
    }
}