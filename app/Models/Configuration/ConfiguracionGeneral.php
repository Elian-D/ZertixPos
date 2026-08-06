<?php

namespace App\Models\Configuration;

use App\Models\Geo\Country;
use App\Models\Geo\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionGeneral extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_generales';

    protected $fillable = [
        'nombre_empresa',
        'logo',
        'tax_id',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'country_id',
        'state_id',
        'impuesto_id',
        'currency',
        'currency_name',
        'currency_symbol',
        'timezone',
        'tax_identifier_type_id',
    ];

    // Relaciones
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class);
    }

    public function taxIdentifierType()
    {
        return $this->belongsTo(TaxIdentifierType::class);
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
