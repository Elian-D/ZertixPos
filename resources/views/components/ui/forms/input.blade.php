{{--
    x-ui.forms.input
    ----------------
    Props: label, name, id, type, placeholder, iconLeft, iconRight, error, hint, required, disabled, readonly
    Extra: cualquier atributo HTML o Livewire (wire:model, x-model, wire:model.live, etc.) se pasa al <input>.

    REQ-7.11: type="password" trae el toggle mostrar/ocultar de fábrica — ver
    isPassword() en Input.php. El x-data va condicional en el wrapper para no
    meter una instancia de Alpine vacía en cada input que no la necesita.

    v1.3.0 Fase 7.9 (fix CSS): con error, `focused` neutraliza el borde/ring/
    fondo rojo mientras el usuario tiene el campo enfocado (el usuario está
    corrigiendo, no tiene sentido seguir gritándole "esto sigue mal"). El
    ícono/mensaje de error se quedan visibles igual — solo se apaga el marco
    rojo del campo. Ver docs/ui/forms.md "Foco sobre un campo con error".
--}}

<div class="flex flex-col min-w-0 group" @if ($alpineData()) x-data="{{ $alpineData() }}" @endif>

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

    {{-- Input wrapper — con addon, el borde/radio/ring los lleva este div
         (groupWrapperClasses()); sin addon, es puramente posicional (el
         <input> lleva su propio borde vía inputClasses()). --}}
    <div class="{{ $hasAddon() ? $groupWrapperClasses() : 'relative flex items-center' }}"
         @if ($error && $hasAddon()) :class="focused ? '!border-zertix-primary !ring-1 !ring-zertix-primary/20 !bg-white' : ''" @endif
    >

        @if ($addonLeft)
            <span class="{{ $addonClasses() }}">{{ $addonLeft }}</span>
        @endif

        {{-- Icono izquierdo --}}
        @if ($iconLeft)
            <span class="{{ $iconWrapClasses() }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        <input
            @if ($isPassword())
                :type="showPassword ? 'text' : 'password'"
            @else
                type="{{ $type }}"
            @endif
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            @disabled($disabled)
            @readonly($readonly)
            @required($required)
            @if ($error)
                @focus="focused = true"
                @blur="focused = false"
                :class="focused ? '!border-zertix-primary !ring-zertix-primary/20 !bg-white !text-slate-800' : ''"
            @endif
            {{ $attributes->merge(['class' => $inputClasses()]) }}
        />

        {{-- v1.3.0 Fase 7.9 (fix CSS): en password, el toggle mostrar/ocultar
             SIEMPRE gana el slot derecho, incluso con error — taparlo con el
             ícono de error deja al usuario sin forma de revisar qué escribió
             mal justo cuando más lo necesita. El error se sigue viendo igual
             (borde/fondo rojo + mensaje debajo), y el propio botón se pinta
             de rojo vía iconColorClasses() cuando hay error. --}}
        @if ($isPassword())
            {{-- A diferencia del icono derecho estático, este SÍ necesita
                 recibir clicks — no reutiliza iconWrapClasses() porque esa
                 clase trae pointer-events-none. --}}
            <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 transition-colors duration-200 {{ $iconColorClasses() }}"
                aria-label="Mostrar/ocultar contraseña"
            >
                <x-heroicon-s-eye-slash x-show="!showPassword" class="w-5 h-5" />
                <x-heroicon-s-eye x-show="showPassword" x-cloak class="w-5 h-5" />
            </button>
        @elseif ($error)
            <span class="{{ $hasAddon() ? 'flex items-center pr-3' : $iconWrapClasses(right: true) }} text-state-error">
                <x-heroicon-s-exclamation-circle class="w-5 h-5" />
            </span>
        @elseif ($iconRight)
            <span class="{{ $iconWrapClasses(right: true) }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconRight" class="w-5 h-5" />
            </span>
        @endif

        @if ($addonRight)
            <span class="{{ $addonClasses(right: true) }}">{{ $addonRight }}</span>
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
