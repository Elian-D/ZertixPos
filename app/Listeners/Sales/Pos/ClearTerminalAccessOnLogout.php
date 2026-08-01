<?php

namespace App\Listeners\Sales\Pos;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class ClearTerminalAccessOnLogout
{
    /**
     * Limpia todas las llaves de verificación de terminales cuando el usuario sale.
     */
    public function handle(Logout $event): void
    {
        // Obtenemos todas las llaves de la sesión actual
        $allKeys = array_keys(Session::all());

        // Filtramos y eliminamos las que corresponden a verificaciones de terminal
        foreach ($allKeys as $key) {
            if (str_starts_with($key, 'terminal_verified.')) {
                Session::forget($key);
            }
        }
    }
}