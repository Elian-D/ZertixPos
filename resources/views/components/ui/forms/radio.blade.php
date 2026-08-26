{{--
    x-ui.forms.radio
    ----------------
    Props: label, name, id, value, checked, description, disabled
    El punto interior verde se logra con `text-zertix-primary` via @tailwindcss/forms.
    Agrupar múltiples radios con el mismo `name` para selección exclusiva.
--}}

<label
    for="{{ $id }}"
    class="flex items-start gap-3 cursor-pointer group {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
>
    {{-- Radio nativo estilizado --}}
    <input
        type="radio"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($checked)
        @disabled($disabled)
        {{ $attributes->merge([
            'class' => 'mt-0.5 w-5 h-5 bg-white cursor-pointer transition-colors duration-200
                        text-zertix-primary focus:ring-zertix-primary focus:ring-offset-0 focus:ring-2
                        border-2 border-slate-300
                        checked:border-zertix-primary
                        disabled:cursor-not-allowed',
        ]) }}
    />

    {{-- Texto --}}
    <div class="flex flex-col min-w-0">
        <span class="text-sm font-medium text-slate-700 group-hover:text-zertix-secondary leading-snug transition-colors duration-200 select-none break-words">
            {{ $label }}
        </span>
        @if ($description)
            <span class="text-xs text-slate-400 mt-0.5 leading-snug break-words">
                {{ $description }}
            </span>
        @endif
    </div>
</label>
