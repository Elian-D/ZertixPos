{{--
    x-data-table.search — buscador del toolbar del motor Livewire.

    El texto sale de la tabla que se está renderizando (v1.4.0 REQ-3.20 e):
    DataTable::searchPlaceholder() arma "Buscar por número o cliente…" desde
    searchFields(), así el buscador dice en qué busca sin que cada vista pase
    nada. Livewire::current() es el componente en render — base-table es un
    componente Blade anónimo y no ve las variables de la vista Livewire.

    PROPS:
      placeholder — fuerza un texto (gana sobre el del motor; lo usan los
                    buscadores del motor AJAX viejo).
      filterKey   — clave en $filters (default 'search').
--}}
@props([
    'placeholder' => null,
    'filterKey'   => 'search',
])

@php
    $table = \Livewire\Livewire::current();
    $placeholder ??= $table && method_exists($table, 'searchPlaceholder')
        ? $table->searchPlaceholder()
        : 'Buscar...';
@endphp

<div class="relative group flex-1 min-w-0 max-w-sm">
    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none
                 text-slate-400
                 group-focus-within:text-zertix-primary transition-colors duration-200">
        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
    </span>

    <input
        type="text"
        wire:model.live.debounce.300ms="filters.{{ $filterKey }}"
        placeholder="{{ $placeholder }}"
        title="{{ $placeholder }}"
        aria-label="{{ $placeholder }}"
        autocomplete="off"
        class="w-full pl-9 pr-8 py-2 text-sm rounded-xl border text-ellipsis
               transition-all duration-200 focus:outline-none focus:ring-0
               bg-white
               border-slate-200
               text-slate-700
               placeholder:text-slate-400
               focus:border-zertix-primary/50"
    />

    <button
        x-show="$wire.filters?.{{ $filterKey }} !== '' && $wire.filters?.{{ $filterKey }} != null"
        x-cloak
        wire:click="$set('filters.{{ $filterKey }}', '')"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 rounded
               text-slate-300
               hover:text-zertix-primary
               transition-colors duration-200"
        title="Limpiar búsqueda">
        <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
    </button>
</div>
