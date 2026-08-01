<?php

namespace App\Services\Sales\Pos\PosConfig;

use App\Models\Sales\Pos\PosSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PosConfigService
{
    /**
     * Actualiza la configuración global del POS
     */
    public function update(array $data): PosSetting
    {
        return DB::transaction(function () use ($data) {
            $settings = PosSetting::first();
            
            if (!$settings) {
                $settings = new PosSetting();
            }

            // Asegurar que los booleanos están presentes
            $data = array_merge([
                'allow_quick_customer_creation' => false,
                'allow_quote_without_save' => false,
                'auto_print_receipt' => false,
            ], $data);

            $settings->fill($data);
            $settings->save();

            // Limpiar cache
            Cache::forget('pos_settings_global');

            return $settings;
        });
    }
}