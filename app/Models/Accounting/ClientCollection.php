<?php

namespace App\Models\Accounting;

use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCollection extends Model
{
    use SoftDeletes;

    // La tabla física sigue llamándose 'payments' — renombrarla exigiría migrar
    // datos históricos (recibos ya impresos con prefijo PAG-) sin ganar nada real;
    // solo la clase Eloquent y todo lo que la consume cambian de nombre (REQ-4.2).
    protected $table = 'payments';

    protected $fillable = [
        'client_id',
        'receivable_id',
        'tipo_pago_id',
        'journal_entry_id',
        'receipt_number',
        'amount',
        'payment_date',
        'reference',
        'note',
        'created_by',
        'status'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    // Constantes de Estado
    const STATUS_ACTIVE    = 'active';    // Recibo válido y contabilizado
    const STATUS_CANCELLED = 'cancelled'; // Recibo anulado (Reversión)

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE    => 'Aplicado',
            self::STATUS_CANCELLED => 'Anulado',
        ];
    }

    public static function getStatusStyles(): array
    {
        return [
            // Emerald/Verde para indicar que el dinero entró y el registro está sano
            self::STATUS_ACTIVE    => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            // Rojo/Gris para indicar que el pago fue invalidado
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700 border-red-200 ring-red-500/10',
        ];
    }
    /* ===========================
     |      RELACIONES
     =========================== */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ===========================
     |    ACCESSORS DE ESTILO
     =========================== */

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusStyleAttribute(): string
    {
        return self::getStatusStyles()[$this->status] ?? 'bg-gray-100 text-gray-700';
    }
}