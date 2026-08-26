<?php

namespace App\Models\Sales\Ncf;

use App\Models\Sales\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcfLog extends Model
{
    const STATUS_USED   = 'used';
    const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'full_ncf', 'sale_id', 'ncf_type_id', 'ncf_sequence_id', 
        'status', 'cancellation_reason', 'user_id'
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_USED   => 'Utilizado',
            self::STATUS_VOIDED => 'Anulado',
        ];
    }

    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_USED   => 'bg-zertix-primary-100 text-zertix-primary-700 border-zertix-primary-200 ring-zertix-primary-500/10',
            self::STATUS_VOIDED => 'bg-gray-100 text-gray-700 border-gray-200 ring-gray-500/10',
        ];
    }

    /**
     * Relaciones
     */
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function type(): BelongsTo { return $this->belongsTo(NcfType::class, 'ncf_type_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    // En el modelo NcfLog.php agrega:
public function sequence(): BelongsTo 
{ 
    return $this->belongsTo(NcfSequence::class, 'ncf_sequence_id'); 
}
    
}