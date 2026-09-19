{{--
    x-ui.forms.select
    -----------------
    Props: label, name, id, placeholder, iconLeft, error, hint, required, disabled

    v1.3.0 Fase 7.9 (fix CSS): con error, `focused` neutraliza el borde/ring
    rojo mientras el select está enfocado — mismo fix que x-ui.forms.input,
    ver docs/ui/forms.md "Foco sobre un campo con error".
    Slot: <option> elements
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

    {{-- Select wrapper --}}
    <div class="relative flex items-center">

        {{-- Icono izquierdo opcional --}}
        @if ($iconLeft)
            <span class="{{ $iconWrapClasses() }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        <select
            name="{{ $name }}"
            id="{{ $id }}"
            @disabled($disabled)
            @required($required)
            @if ($error)
                @focus="focused = true"
                @blur="focused = false"
                :class="focused ? '!border-zertix-primary !ring-zertix-primary/20 !bg-white !text-slate-800' : ''"
            @endif
            {{ $attributes->merge(['class' => $selectClasses()]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>

        {{-- Chevron (siempre visible, el select nativo pierde el suyo con appearance-none) --}}
        <span class="{{ $iconWrapClasses(right: true) }} {{ $iconColorClasses() }}">
            <x-heroicon-s-chevron-down class="w-4 h-4" />
        </span>

    </div>

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
