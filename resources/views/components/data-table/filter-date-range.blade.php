@props([
    'label'   => '',
    'fromKey' => 'date_from',
    'toKey'   => 'date_to',
])

<div class="space-y-2">
    @if($label)
        <label class="block text-[11px] font-bold uppercase tracking-wider
                      text-slate-400">
            {{ $label }}
        </label>
    @endif

    <div class="grid grid-cols-2 gap-2">
        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400">Desde</p>
            <input
                type="date"
                wire:model.live="filters.{{ $fromKey }}"
                class="w-full px-3 py-2 text-sm rounded-xl border
                       transition-all duration-200 focus:outline-none focus:ring-0
                       bg-white
                       border-slate-200
                       text-slate-700
                       focus:border-zertix-primary/50"
            />
        </div>

        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400">Hasta</p>
            <input
                type="date"
                wire:model.live="filters.{{ $toKey }}"
                class="w-full px-3 py-2 text-sm rounded-xl border
                       transition-all duration-200 focus:outline-none focus:ring-0
                       bg-white
                       border-slate-200
                       text-slate-700
                       focus:border-zertix-primary/50"
            />
        </div>
    </div>

    <div x-show="$wire.filters?.{{ $fromKey }} || $wire.filters?.{{ $toKey }}"
         x-cloak
         class="flex justify-end">
        <button
            wire:click="$set('filters.{{ $fromKey }}', ''); $set('filters.{{ $toKey }}', '')"
            class="text-[11px] font-medium text-slate-400
                   hover:text-zertix-primary-dark
                   transition-colors duration-200">
            Limpiar fechas
        </button>
    </div>
</div>
