@props([
    'options' => [10, 25, 50, 100],
])

<div class="flex items-center gap-2 flex-shrink-0">
    <span class="text-xs font-medium text-slate-400 whitespace-nowrap hidden sm:block">
        Mostrar
    </span>

    <div class="relative">
        <select
            wire:model.live="perPage"
            class="appearance-none pl-3 pr-7 py-2 text-sm font-semibold rounded-xl border
                   cursor-pointer transition-all duration-200 focus:outline-none focus:ring-0
                   bg-white
                   border-slate-200
                   text-slate-700
                   hover:border-slate-300
                   focus:border-zertix-primary/50">
            @foreach($options as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>
    </div>
</div>
