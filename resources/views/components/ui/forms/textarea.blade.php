{{--
    x-ui.forms.textarea
    -------------------
    Props: label, name, id, placeholder, rows, error, hint, required, disabled, readonly, resize

    v1.3.0 Fase 7.9 (fix CSS): con error, `focused` neutraliza el borde/ring
    rojo mientras el textarea está enfocado — mismo fix que x-ui.forms.input,
    ver docs/ui/forms.md "Foco sobre un campo con error".
--}}

<div class="flex flex-col min-w-0 group" @if ($error) x-data="{ focused: false }" @endif>

    {{-- Label --}}
    @if ($label)
        <label
            for="{{ $id }}"
            class="text-xs font-semibold mb-1.5 block transition-colors duration-200
                   {{ $error ? 'text-state-error' : 'text-slate-600' }}"
            @if ($error) :class="focused ? '!text-slate-600' : ''" @endif
        >
            {{ $label }}
            @if ($required)
                <span class="text-state-error ml-0.5">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @disabled($disabled)
        @readonly($readonly)
        @required($required)
        @if ($error)
            @focus="focused = true"
            @blur="focused = false"
            :class="focused ? '!border-zertix-primary !ring-zertix-primary/20 !bg-white !text-slate-800' : ''"
        @endif
        {{ $attributes->merge(['class' => $textareaClasses()]) }}
    ></textarea>

    {{-- Mensaje de error o hint --}}
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-state-error break-words">
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-400 break-words">
            {{ $hint }}
        </p>
    @endif

</div>
