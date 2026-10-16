{{-- strong: valores que salen en negrita (ej. la categoría principal en un filtro en árbol,
     v1.5.1 REQ-2.4); las hijas llegan con sangría en su etiqueta. --}}
@props([
    'label'       => '',
    'filterKey'   => '',
    'options'     => [],
    'placeholder' => 'Todos',
    'strong'      => [],
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-[11px] font-bold uppercase tracking-wider
                      text-slate-400">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            wire:model.live="filters.{{ $filterKey }}"
            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm rounded-xl border
                   cursor-pointer transition-all duration-200 focus:outline-none focus:ring-0
                   bg-white
                   border-slate-200
                   text-slate-700
                   focus:border-zertix-primary/50"
            :class="$wire.filters?.{{ $filterKey }} !== '' && $wire.filters?.{{ $filterKey }} != null
                ? 'border-zertix-primary/40 bg-zertix-primary/5 text-zertix-primary-dark font-semibold'
                : ''"
        >
            <option value="">{{ $placeholder }}</option>
            @foreach($options as $value => $label)
                <option value="{{ $value }}" @if(in_array($value, $strong)) class="font-bold text-slate-900" @endif>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
