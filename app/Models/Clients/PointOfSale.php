<?php

namespace App\Models\Clients;

use App\Models\Geo\Province;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointOfSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'point_of_sales';

    protected $fillable = [
        'client_id',
        'business_type_id',
        'name',
        'provincia_id',
        'city',
        'address',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'notes',
        'active',
    ];

    /* ===========================
     |      RELACIONES
     =========================== */

    /**
     * Cliente propietario del punto de venta
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Tipo de negocio (colmado, farmacia, drink, etc.)
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    /**
     * Provincia geográfica
     */
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provincia_id');
    }

    /* ===========================
     |    ACCESSORS
     =========================== */

    /**
     * Nombre visible del PDV para tablas y selects
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /* ===========================
     |      SCOPES
     =========================== */

    /**
     * Relaciones necesarias para el index AJAX
     */
    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'client:id,name,commercial_name,tax_id',
            'businessType:id,nombre',
            'provincia:id,name',
        ]);
    }

    /**
     * Solo puntos de venta activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
