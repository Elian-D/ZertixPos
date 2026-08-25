{{--
    x-ui.forms.checkbox
    -------------------
    Props: label, name, id, value, checked, description, error, disabled
    Usa @tailwindcss/forms para el estilo del check nativo. `text-zertix-primary` define el color del tilde.
--}}

<div class="flex flex-col gap-1">
    <label
        for="{{ $id }}"
        class="flex items-start gap-3 cursor-pointer group {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
    >
        {{-- Checkbox nativo estilizado con @tailwindcss/forms --}}
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            @checked($checked)
            @disabled($disabled)
            {{ $attributes->merge([
                'class' => 'mt-0.5 w-5 h-5 rounded border-2 bg-white cursor-pointer transition-colors duration-200
                            text-zertix-primary focus:ring-zertix-primary focus:ring-offset-0 focus:ring-2
                            border-slate-300
                            checked:border-zertix-primary
                            disabled:cursor-not-allowed',
            ]) }}
        />

        {{-- Texto del label --}}
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

    @if ($error)
        <p class="ml-8 text-xs font-medium text-state-error">{{ $error }}</p>
    @endif
</div>
