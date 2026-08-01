<?php

namespace App\Models\Sales\Pos;

use Illuminate\Database\Eloquent\Model;
use App\Models\Clients\Client;
use Illuminate\Support\Facades\Cache;

class PosSetting extends Model
{
    protected $fillable = [
        'allow_quick_customer_creation',
        'default_walkin_customer_id',
        'allow_quote_without_save',
        'auto_print_receipt',
        'receipt_size',
    ];

    protected $casts = [
        'allow_quick_customer_creation' => 'boolean',
        'allow_quote_without_save' => 'boolean',
        'auto_print_receipt' => 'boolean',
    ];

    /**
     * Singleton Pattern con Cache
     * Acceso: PosSetting::getSettings()
     */
    public static function getSettings()
    {
        return Cache::rememberForever('pos_settings_global', function () {
            return self::first() ?? self::createDefault();
        });
    }

    /**
     * Crea configuración por defecto si no existe
     */
    public static function createDefault()
    {
        return self::create([
            'allow_quick_customer_creation' => true,
            'default_walkin_customer_id' => 1, // Asumiendo que 1 es Consumidor Final
            'allow_quote_without_save' => true,
            'auto_print_receipt' => true,
            'receipt_size' => '80mm',
        ]);
    }

    /**
     * Limpiar cache al actualizar
     */
    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('pos_settings_global');
        });
    }

    // Relaciones
    public function defaultCustomer()
    {
        return $this->belongsTo(Client::class, 'default_walkin_customer_id');
    }
}