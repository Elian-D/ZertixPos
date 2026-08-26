<?php

namespace App\Livewire\Configuration;

use App\Models\Accounting\Receivable;
use App\Models\Configuration\InstallationModule;
use App\Models\Inventory\InventoryMovement;
use App\Models\Sales\Quotes\Quote;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SystemFeatures extends Component
{
    /**
     * safe_key => bool, estado local, no persistido hasta guardar (REQ-10.6).
     *
     * "safe_key" = module_key con '.' reemplazado por '__'. Los module_key reales
     * (ej. 'inventory.tracking') NO pueden usarse tal cual como llave de un array en
     * una propiedad pública de Livewire: el protocolo de sincronización de Livewire
     * interpreta el punto como separador de ruta anidada al diffear el estado
     * cliente↔servidor, así que 'inventory.tracking' llegaba truncado a 'inventory'
     * en el servidor (bug real, reproducido: INSERT con module_key='inventory' y
     * error "Array to string conversion" porque is_enabled llegaba como array
     * anidado en vez de bool). safeKey()/realKey() traducen en los bordes.
     */
    public array $selections = [];

    /** safe_key => texto de advertencia si se está apagando algo con datos. */
    public array $warnings = [];

    /**
     * Satélite → Flexible: no se cascadea, se BLOQUEA la combinación inválida al
     * guardar (REQ-10.7) — el dueño decide cuál de los dos apagar.
     */
    protected const HARD_DEPENDENCIES = [
        'purchases.vendors' => 'inventory.tracking',
    ];

    /**
     * Satélite → Satélite: sí se cascadea automático en el estado local al
     * desmarcar el padre (REQ-10.7) — pocos casos. Vacío por ahora: el único caso
     * que existía (sales.ncf → sales.credit_notes_b04) se eliminó en REQ-10.9 — B04
     * no es un módulo propio, es una validación dentro de Devoluciones (todavía sin
     * construir) contra module_enabled('sales.ncf') directo, no un segundo flag.
     */
    protected const CASCADE_DEPENDENCIES = [];

    protected function safeKey(string $moduleKey): string
    {
        return str_replace('.', '__', $moduleKey);
    }

    protected function realKey(string $safeKey): string
    {
        return str_replace('__', '.', $safeKey);
    }

    public function mount(): void
    {
        foreach (InstallationModule::pluck('is_enabled', 'module_key') as $moduleKey => $enabled) {
            $this->selections[$this->safeKey($moduleKey)] = (bool) $enabled;
        }
    }

    public function toggle(string $safeKey): void
    {
        $this->selections[$safeKey] = ! ($this->selections[$safeKey] ?? false);

        $this->applyCascadeAndWarnings($safeKey);
    }

    /**
     * Hook automático de Livewire: se dispara solo cuando un `wire:model` de ruta
     * anidada (ej. "selections.sales__delivery_points", usado por x-ui.forms.toggle
     * vía @entangle) escribe en $selections. Reemplaza la necesidad de wire:click +
     * remount forzado del toggle — con @entangle el switch mantiene su animación y
     * su estado de Alpine vivo entre renders, sin el bug de desincronización que
     * causaba el patrón wire:click anterior (ver docs/ui/forms.md).
     */
    public function updatedSelections(bool $value, string $safeKey): void
    {
        $this->applyCascadeAndWarnings($safeKey);
    }

    /**
     * Cascada automática satélite→satélite: si se apaga el padre, se apaga el hijo
     * en el mismo estado local (nunca al revés, encender el padre no reactiva nada).
     * Compartido entre toggle() (legacy) y updatedSelections() (wire:model).
     */
    protected function applyCascadeAndWarnings(string $safeKey): void
    {
        if (! ($this->selections[$safeKey] ?? false)) {
            foreach (self::CASCADE_DEPENDENCIES[$this->realKey($safeKey)] ?? [] as $dependent) {
                $dependentSafeKey = $this->safeKey($dependent);
                $this->selections[$dependentSafeKey] = false;
                $this->warnings[$dependentSafeKey] = $this->buildWarningIfAny($dependentSafeKey);
            }
        }

        $this->warnings[$safeKey] = $this->buildWarningIfAny($safeKey);
    }

    public function save(): void
    {
        if (! $this->validateHardDependencies()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->selections as $safeKey => $enabled) {
                InstallationModule::updateOrCreate(
                    ['module_key' => $this->realKey($safeKey)],
                    ['is_enabled' => $enabled]
                );
            }
        });

        // Toast del sistema (REQ-10.9) — session flash, se lee en el próximo request
        // porque el redirect de abajo es una navegación real, no un re-render en vivo.
        session()->flash('success', 'Cambios guardados correctamente.');

        // Recarga completa (no navigate) — module_enabled()/general_config() están
        // memoizados `static` por request, el sidebar y las rutas gateadas por
        // middleware `module:` viven fuera del ciclo de vida de este componente
        // Livewire (REQ-10.6).
        $this->redirect(request()->header('Referer') ?? route('configuration.features'), navigate: false);
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, icon: string, includedInPlan: bool}>
     */
    public function satelliteModules(): array
    {
        $planModules = $this->planModules();

        return collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'satellite')
            ->map(fn (array $module, string $key) => [
                'key' => $this->safeKey($key),
                'label' => $module['label'],
                'description' => $module['description'] ?? '',
                'icon' => $module['icon'] ?? 'heroicon-s-puzzle-piece',
                'includedInPlan' => in_array($key, $planModules, true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, icon: string}>
     */
    public function flexibleModules(): array
    {
        return collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'base_flexible')
            ->map(fn (array $module, string $key) => [
                'key' => $this->safeKey($key),
                'label' => $module['label'],
                'description' => $module['description'] ?? '',
                'icon' => $module['icon'] ?? 'heroicon-s-puzzle-piece',
            ])
            ->values()
            ->all();
    }

    public function planModules(): array
    {
        return current_plan()?->moduleKeys() ?? [];
    }

    /**
     * Advertencia si se está apagando un flexible que ya tiene datos reales —
     * el módulo se congela, no se borra nada, pero el dueño debería saberlo
     * antes de guardar (REQ-10.5/10.6).
     */
    protected function buildWarningIfAny(string $safeKey): ?string
    {
        if ($this->selections[$safeKey] ?? true) {
            return null;
        }

        $hasData = match ($this->realKey($safeKey)) {
            'inventory.tracking' => InventoryMovement::exists(),
            'sales.receivables' => Receivable::exists(),
            'sales.quotes' => Quote::exists(),
            default => false,
        };

        if (! $hasData) {
            return null;
        }

        return 'Ya existen registros de este tipo — no se borran, pero dejan de crearse nuevos mientras esté apagado.';
    }

    /**
     * Bloquea, nunca cascadea, entre satélite y flexible (REQ-10.7) — lista corta
     * mantenida a mano, no un resolver genérico.
     */
    protected function validateHardDependencies(): bool
    {
        foreach (self::HARD_DEPENDENCIES as $satellite => $requiredFlexible) {
            $satelliteOn = $this->selections[$this->safeKey($satellite)] ?? false;
            $flexibleOn = $this->selections[$this->safeKey($requiredFlexible)] ?? false;

            if ($satelliteOn && ! $flexibleOn) {
                // Ojo: los module_key llevan punto en el nombre (ej. 'purchases.vendors'),
                // así que config('modules.'.$key.'.label') con dot-notation se rompería —
                // Laravel lo interpretaría como una ruta anidada, no como la clave plana
                // que realmente es. Acceso directo al array, sin dot-notation.
                $modules = config('modules');
                $satelliteLabel = $modules[$satellite]['label'] ?? $satellite;
                $flexibleLabel = $modules[$requiredFlexible]['label'] ?? $requiredFlexible;

                // Toast del sistema (REQ-10.9) — acá no hay redirect (save() corta antes
                // de guardar nada), así que no sirve session()->flash(): se dispara un
                // evento de navegador que <x-ui.toasts /> escucha en vivo sin recargar.
                $this->dispatch(
                    'toast',
                    type: 'error',
                    title: 'No se pudo guardar',
                    message: "No podés apagar \"{$flexibleLabel}\" mientras \"{$satelliteLabel}\" esté activo."
                );

                return false;
            }
        }

        return true;
    }

    public function render()
    {
        return view('livewire.configuration.system-features');
    }
}
