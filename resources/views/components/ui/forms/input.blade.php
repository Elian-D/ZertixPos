{{--
    x-ui.forms.input
    ----------------
    Props: label, name, id, type, placeholder, iconLeft, iconRight, error, hint, required, disabled, readonly
    Extra: cualquier atributo HTML o Livewire (wire:model, x-model, wire:model.live, etc.) se pasa al <input>.
--}}

<div class="flex flex-col min-w-0 group">

    {{-- Label --}}
    @if ($label)
        <label
            for="{{ $id }}"
            class="text-xs font-semibold mb-1.5 block transition-colors duration-200
                   {{ $error ? 'text-state-error' : 'text-slate-600' }}"
        >
            {{ $label }}
            @if ($required)
                <span class="text-state-error ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{-- Input wrapper --}}
    <div class="relative flex items-center">

        {{-- Icono izquierdo --}}
        @if ($iconLeft)
            <span class="{{ $iconWrapClasses() }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            @disabled($disabled)
            @readonly($readonly)
            @required($required)
            {{ $attributes->merge(['class' => $inputClasses()]) }}
        />

        {{-- Icono error (reemplaza el icono derecho) --}}
        @if ($error)
            <span class="{{ $iconWrapClasses(right: true) }} text-state-error">
                <x-heroicon-s-exclamation-circle class="w-5 h-5" />
            </span>
        @elseif ($iconRight)
            <span class="{{ $iconWrapClasses(right: true) }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconRight" class="w-5 h-5" />
            </span>
        @endif

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
