<?php
namespace App\Models\Accounting;

use App\Models\Clients\Client;
use Illuminate\Database\Eloquent\{Model, SoftDeletes, Relations\BelongsTo, Relations\HasMany, Relations\MorphTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Receivable extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'client_id',
        'journal_entry_id',
        'accounting_account_id',
        'reference_type', // Agregado
        'reference_id',   // Agregado
        'document_number',
        'description',
        'total_amount',
        'current_balance',
        'emission_date',
        'due_date',
        'status'
    ];

    protected $casts = [
        'emission_date' => 'date',
        'due_date'      => 'date',
        'total_amount'  => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    // Constantes de Estado
    const STATUS_UNPAID    = 'unpaid';
    const STATUS_PARTIAL   = 'partial';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID    => 'Pendiente',
            self::STATUS_PARTIAL   => 'Abonado',
            self::STATUS_PAID      => 'Pagado',
            self::STATUS_CANCELLED => 'Anulado',
        ];
    }

    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_UNPAID    => 'bg-red-100 text-red-700 border-red-200 ring-red-500/10',
            self::STATUS_PARTIAL   => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500/10',
            self::STATUS_PAID      => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-700 border-gray-200 ring-gray-500/10',
        ];
    }
    
    // Relación Polimórfica: Permite obtener el objeto origen (Sale, etc.)
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accesor para obtener la venta si la referencia es de tipo Sale
     */
    public function getSaleAttribute()
    {
        return $this->reference_type === \App\Models\Sales\Sale::class 
            ? $this->reference 
            : null;
    }

    /**
     * Forzamos la comparación usando parse para asegurar objetos Carbon
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        // Convertimos explícitamente a Carbon y comparamos solo fechas
        $today = Carbon::now()->startOfDay();
        $due = Carbon::parse($this->due_date)->startOfDay();

        return $today->gt($due);
    }

    /**
     * Etiqueta legible del estado
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    // Relaciones estándar
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function accountingAccount(): BelongsTo { return $this->belongsTo(AccountingAccount::class); }

    /**
     * Cobros aplicados a esta CxC (activos o anulados) — el hecho histórico que
     * determina si ya se puede cancelar (Fase 6, REQ-6.11), no el saldo mutable.
     */
    public function collections(): HasMany { return $this->hasMany(ClientCollection::class, 'receivable_id'); }
}