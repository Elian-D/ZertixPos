<?php

use Illuminate\Support\Facades\Lang;

if (!function_exists('trans_permission')) {
    function trans_permission(string $name, string $key = 'label'): string
    {
        $langKey = "permissions.{$name}.{$key}";

        if (Lang::has($langKey)) {
            return __($langKey);
        }

        return ucfirst(str_replace(['.', '_'], ' ', $name));
    }
}
