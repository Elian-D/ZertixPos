<?php

namespace App\Models\Configuration;

use App\Enums\TaxIdentifierType;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionGeneral extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_generales';

    protected $fillable = [
        'plan_id',
        'nombre_empresa',
        'logo',
        'tax_id',
        'tax_identifier_type',
        'telefono',
        'email',
        'direccion',
        'provincia_id',
        'municipio_id',
        'impuesto_id',
    ];

    protected $casts = [
        'tax_identifier_type' => TaxIdentifierType::class,
    ];

    // Relaciones
    public function provincia()
    {
        return $this->belongsTo(Province::class, 'provincia_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipality::class, 'municipio_id');
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class);
    }

    // Obtener la configuración general actual (única fila)
    public static function actual()
    {
        return self::first();
    }

    /**
     * Determina si el sistema opera bajo normativa de la DGII (NCF/e-NCF).
     * Delega al registro de módulos (Fase 3/4) — el booleano que antes vivía en
     * esta tabla ahora es el flag 'sales.ncf' en installation_modules.
     */
    public function esModoFiscal(): bool
    {
        return module_enabled('sales.ncf');
    }
}
