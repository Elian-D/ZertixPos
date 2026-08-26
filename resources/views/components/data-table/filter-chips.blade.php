@props([
    'chips'      => [],
    'hasFilters' => false,
])

@if($hasFilters && count($chips) > 0)
    <div class="flex flex-wrap items-center gap-2 px-1 pb-3 -mt-1">

        @foreach($chips as $chip)
            <span class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1
                         rounded-full border text-xs font-semibold
                         bg-zertix-primary/8
                         border-zertix-primary/25
                         text-zertix-primary-dark">

                <span class="text-zertix-primary-dark/60 font-medium">{{ $chip['label'] }}:</span>
                <span>{{ $chip['value'] }}</span>

                <button
                    wire:click="clearFilter('{{ $chip['key'] }}')"
                    class="flex items-center justify-center w-4 h-4 rounded-full
                           text-zertix-primary-dark/60 hover:text-zertix-primary-dark
                           hover:bg-zertix-primary/15
                           transition-all duration-150"
                    title="Quitar filtro {{ $chip['label'] }}">
                    <x-heroicon-s-x-mark class="w-2.5 h-2.5" />
                </button>
            </span>
        @endforeach

        <button
            wire:click="clearAllFilters"
            class="text-xs font-semibold text-slate-400
                   hover:text-zertix-primary-dark
                   transition-colors duration-200 ml-1">
            Limpiar todo
        </button>

    </div>
@endif
