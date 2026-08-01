<?php

if (!function_exists('pos_config')) {
    function pos_config($key = null)
    {
        $settings = \App\Models\Sales\Pos\PosSetting::getSettings();
        
        if (is_null($key)) {
            return $settings;
        }

        return $settings->$key ?? null;
    }
}