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
        'dias_gracia_mora',
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

    // Obtener la configuración general actual (única fila)
    public static function actual()
    {
        return self::first();
    }

    /**
     * Indica si el Wizard de Instalación (Fase 8) ya se completó. ConfiguracionGeneralSeeder
     * deja `nombre_empresa` en '' (string vacío, no NULL) hasta que el Wizard lo llena de
     * verdad — un whereNotNull() plano ya daría true con la fila recién sembrada, por eso
     * también se excluye la cadena vacía.
     */
    public static function isInstalled(): bool
    {
        return static::whereNotNull('nombre_empresa')
            ->where('nombre_empresa', '!=', '')
            ->exists();
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
