{{--
    resources/views/components/data-table/bulk-actions-bar.blade.php
    --------------------------------------------------------------------
    Barra flotante de selección masiva (REQ-0.5) — no existe en el kit de
    Orvian, construida de cero para ZertixPOS. Visible solo si hay selección.
    Cada botón dispara runBulkAction('key'), que el DataTable base delega
    en performBulkAction() del hijo (el mismo performBulkAction() que ya
    existe en los Services — la barra no reimplementa lógica de negocio).

    PROPS:
      count   — número de filas seleccionadas ($wire.selected)
      actions — array del bulkActions() del hijo:
                [['key' => 'activate', 'label' => 'Activar', 'variant' => 'primary',
                  'icon' => 'heroicon-o-check', 'confirm' => false]]
--}}

@props([
    'count'   => 0,
    'actions' => [],
])

<div
    x-show="$wire.selected.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    x-cloak
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40
           flex items-center gap-3 px-4 py-3 rounded-2xl
           bg-zertix-secondary-dark text-white shadow-2xl shadow-black/20"
>
    <span class="text-sm font-semibold whitespace-nowrap">
        {{ $count }} {{ Str::plural('seleccionado', $count) }}
    </span>

    <div class="h-5 w-px bg-white/20"></div>

    <div class="flex items-center gap-1.5">
        @foreach($actions as $action)
            <button
                type="button"
                @if($action['confirm'] ?? false)
                    wire:click="runBulkAction('{{ $action['key'] }}')"
                    wire:confirm="{{ $action['confirmMessage'] ?? '¿Confirmas esta acción sobre los registros seleccionados?' }}"
                @else
                    wire:click="runBulkAction('{{ $action['key'] }}')"
                @endif
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold
                       transition-colors duration-150
                       {{ ($action['variant'] ?? 'default') === 'error'
                            ? 'bg-red-500/20 text-red-100 hover:bg-red-500/30'
                            : 'bg-white/10 hover:bg-white/20 text-white' }}"
            >
                @if($action['icon'] ?? null)
                    <x-dynamic-component :component="$action['icon']" class="w-3.5 h-3.5" />
                @endif
                {{ $action['label'] }}
            </button>
        @endforeach
    </div>

    <div class="h-5 w-px bg-white/20"></div>

    <button
        type="button"
        wire:click="clearSelection"
        class="text-white/60 hover:text-white transition-colors"
        title="Cancelar selección">
        <x-heroicon-s-x-mark class="w-4 h-4" />
    </button>
</div>
