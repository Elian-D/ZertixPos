@props(['label', 'formId'])

<div class="space-y-2">
    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">
        {{ $label }}
    </label>
    <div class="grid grid-cols-2 gap-2">
        <div class="relative">
            <input type="date" name="from_date" form="{{ $formId }}"
                value="{{ request('from_date') }}"
                class="w-full border-gray-300 rounded-lg text-[11px] px-2 py-1.5 focus:ring-zertix-primary-500 shadow-sm">
            <span class="absolute -top-2 left-2 bg-white px-1 text-[9px] text-gray-400">Desde</span>
        </div>
        <div class="relative">
            <input type="date" name="to_date" form="{{ $formId }}"
                value="{{ request('to_date') }}"
                class="w-full border-gray-300 rounded-lg text-[11px] px-2 py-1.5 focus:ring-zertix-primary-500 shadow-sm">
            <span class="absolute -top-2 left-2 bg-white px-1 text-[9px] text-gray-400">Hasta</span>
        </div>
    </div>
</div>