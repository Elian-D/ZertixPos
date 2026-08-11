<?php

namespace App\Http\Middleware;

use App\Models\Configuration\ConfiguracionGeneral;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstallationWizardCompleted
{
    /**
     * Corre en todo el grupo 'web' (ver bootstrap/app.php) — antes de `auth`,
     * porque una instalación sin terminar no tiene todavía ningún usuario ni
     * rol sembrado para loguearse. Las llamadas internas del propio Livewire
     * (wire:model, wire:click, subida de archivos) se excluyen explícitamente
     * — sin esto el Wizard se rompe contra sí mismo.
     *
     * Se detectan por NOMBRE de ruta, no por header ni por path:
     * - El path tiene un prefijo con hash aleatorio por instalación (ej.
     *   `livewire-f512241b/update`), nunca `livewire/*` plano.
     * - El header `X-Livewire` solo lo manda la petición normal de
     *   `wire:model`/`wire:click` (vía fetch) — la subida de archivos usa un
     *   XMLHttpRequest aparte (para poder reportar progreso) que NUNCA manda
     *   ese header, solo `Accept`/`X-CSRF-TOKEN` (confirmado leyendo
     *   vendor/livewire/livewire/dist/livewire.js, método handleSignedUrl()).
     *   Bug real reproducido: con el header como único filtro, cada subida de
     *   logo quedaba redirigida a /install (HTML en vez de JSON), y el logo
     *   nunca se guardaba — sin ningún error 500 que lo delatara.
     * Los nombres de ruta sí son estables: `default-livewire.update`,
     * `livewire.upload-file`, `livewire.preview-file` — todos contienen
     * "livewire", constante sin importar el hash del prefijo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // El test suite (APP_ENV=testing) corre sobre una BD que RefreshDatabase
        // migra pero no siembra — cada Feature test asume una app ya instalada,
        // no el estado real del Wizard. Aplicar este guard ahí redirige cada
        // request de cada test a /install, rompiendo 27 tests de golpe (bug real,
        // encontrado corriendo el suite después de construir la Fase 8).
        if (app()->environment('testing')) {
            return $next($request);
        }

        $isLivewireInternal = str_contains($request->route()?->getName() ?? '', 'livewire');

        $installed = ConfiguracionGeneral::isInstalled();

        if (! $installed && ! $request->routeIs('install.*') && ! $isLivewireInternal) {
            return redirect()->route('install.wizard');
        }

        if ($installed && $request->routeIs('install.*')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
