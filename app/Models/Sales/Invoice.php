<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id', 
        'invoice_number', 
        'type', 
        'format_type', 
        'status', 
        'due_date', 
        'generated_by'
    ];

    protected $casts = [
        'due_date' => 'date',      
        'created_at' => 'datetime',
    ];

    // Constantes de Estado
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';

    // Constantes de Formato
    const FORMAT_TICKET = 'ticket'; // Planta / 80mm
    const FORMAT_LETTER = 'letter'; // Oficina / Crédito
    const FORMAT_ROUTE  = 'route';  // Camión / 58mm

    /**
     * Nombres legibles para los estados
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE    => 'Vigente',
            self::STATUS_CANCELLED => 'Anulada',
        ];
    }

    /**
     * Nombres legibles para los formatos
     */
    public static function getFormats(): array
    {
        return [
            self::FORMAT_TICKET => 'Ticket (80mm)',
            self::FORMAT_LETTER => 'Carta (Oficina)',
            self::FORMAT_ROUTE  => 'Ruta (58mm)',
        ];
    }

    /**
     * Estilos de Tailwind para los estados
     */
    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_ACTIVE    => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700 border-red-200 ring-red-500/10',
        ];
    }

    /**
     * Iconos para los formatos de impresión
     */
    public static function getFormatIcons(): array
    {
        return [
            self::FORMAT_TICKET => 'heroicon-s-printer',
            self::FORMAT_LETTER => 'heroicon-s-document-text',
            self::FORMAT_ROUTE  => 'heroicon-s-truck',
        ];
    }

    /**
     * Centraliza las relaciones para el Index
     */
    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            // net_amount/tax_amount necesarios para que Sale::grand_total (accessor)
            // resuelva bien acá — sin ellos siempre da 0 (Fase 5, auditoría post-REQ-5.12).
            'sale:id,number,payment_type,client_id,total_amount,net_amount,tax_amount',
            'sale.client:id,name',
        ]);
    }

    /**
     * Relación con la Venta
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}