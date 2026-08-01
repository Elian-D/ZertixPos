<?php

namespace App\Http\Middleware\Sales\Pos;

use Closure;
use Illuminate\Http\Request;
use App\Models\Sales\Pos\PosSetting;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosConfig
{
    /**
     * Verifica que la configuración base del POS exista.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Usamos el método Singleton que ya tiene el Cache
        $settings = PosSetting::getSettings();

        if (!$settings) {
            // Si por un desastre no hay settings y el auto-create del modelo falló
            return redirect()->route('admin.pos.settings.edit')
                ->with('error', 'La configuración base del POS no ha sido inicializada.');
        }

        // Si falta el cliente por defecto, es un error crítico para la integridad de ventas
        if (!$settings->default_walkin_customer_id) {
             // Solo redirigimos si no estamos ya en la página de settings para evitar bucles
            if (!$request->routeIs('admin.pos.settings.*')) {
                return redirect()->route('admin.pos.settings.edit')
                    ->with('warning', 'Debe configurar un Cliente por Defecto (Walk-in) antes de usar el POS.');
            }
        }

        return $next($request);
    }
}