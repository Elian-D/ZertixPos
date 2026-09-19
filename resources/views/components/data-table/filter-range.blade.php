@props([
    'label'   => '',
    'fromKey' => 'range_min',
    'toKey'   => 'range_max',
    'prefix'  => null,
    'suffix'  => null,
    'min'     => 0,
    'step'    => 1,
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
            <p class="text-[10px] font-medium text-slate-400">Mínimo</p>
            <div class="relative">
                @if($prefix)
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 pointer-events-none">
                        {{ $prefix }}
                    </span>
                @endif
                <input
                    type="number"
                    wire:model.live="filters.{{ $fromKey }}"
                    min="{{ $min }}"
                    step="{{ $step }}"
                    placeholder="0"
                    class="w-full py-2 text-sm rounded-xl border
                           transition-all duration-200 focus:outline-none focus:ring-0
                           bg-white
                           border-slate-200
                           text-slate-700
                           focus:border-zertix-primary/50
                           {{ $prefix ? 'pl-8 pr-3' : 'px-3' }}
                           {{ $suffix ? 'pr-8' : '' }}"
                />
                @if($suffix)
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 pointer-events-none">
                        {{ $suffix }}
                    </span>
                @endif
            </div>
        </div>

        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400">Máximo</p>
            <div class="relative">
                @if($prefix)
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 pointer-events-none">
                        {{ $prefix }}
                    </span>
                @endif
                <input
                    type="number"
                    wire:model.live="filters.{{ $toKey }}"
                    min="{{ $min }}"
                    step="{{ $step }}"
                    placeholder="∞"
                    class="w-full py-2 text-sm rounded-xl border
                           transition-all duration-200 focus:outline-none focus:ring-0
                           bg-white
                           border-slate-200
                           text-slate-700
                           focus:border-zertix-primary/50
                           {{ $prefix ? 'pl-8 pr-3' : 'px-3' }}
                           {{ $suffix ? 'pr-8' : '' }}"
                />
                @if($suffix)
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 pointer-events-none">
                        {{ $suffix }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div x-show="$wire.filters?.{{ $fromKey }} || $wire.filters?.{{ $toKey }}"
         x-cloak class="flex justify-end">
        <button
            wire:click="$set('filters.{{ $fromKey }}', ''); $set('filters.{{ $toKey }}', '')"
            class="text-[11px] font-medium text-slate-400
                   hover:text-zertix-primary-dark transition-colors">
            Limpiar rango
        </button>
    </div>
</div>
