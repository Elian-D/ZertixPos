@props(['label', 'nameMin', 'nameMax', 'formId', 'placeholderMin' => 'Mín', 'placeholderMax' => 'Máx'])

<div class="space-y-2">
    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">
        {{ $label }}
    </label>
    <div class="grid grid-cols-2 gap-2">
        <div class="relative">
            <input type="number" name="{{ $nameMin }}" form="{{ $formId }}"
                value="{{ request($nameMin) }}" step="0.01" min="0"
                placeholder="{{ $placeholderMin }}"
                class="w-full border-gray-300 rounded-lg text-[11px] px-2 py-1.5 focus:ring-zertix-primary-500 shadow-sm">
            <span class="absolute -top-2 left-2 bg-white px-1 text-[9px] text-gray-400">Desde</span>
        </div>
        <div class="relative">
            <input type="number" name="{{ $nameMax }}" form="{{ $formId }}"
                value="{{ request($nameMax) }}" step="0.01" min="0"
                placeholder="{{ $placeholderMax }}"
                class="w-full border-gray-300 rounded-lg text-[11px] px-2 py-1.5 focus:ring-zertix-primary-500 shadow-sm">
            <span class="absolute -top-2 left-2 bg-white px-1 text-[9px] text-gray-400">Hasta</span>
        </div>
    </div>
</div>