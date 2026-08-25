{{--
    resources/views/components/ui/forms/toggle.blade.php
    -----------------------------------------------------
    Tres formas de manejar el estado, en este orden de prioridad:
    1. `x-model="alpineExpr"` — bind de Alpine puro de doble vía, para cuando un
       elemento HERMANO/ANCESTRO fuera de este componente (ej. el borde de una
       tarjeta, un panel `x-show`, un input que se deshabilita junto al toggle)
       necesita leer/escribir el mismo booleano. No se declara "on" local: se
       lee la expresión del padre directo en :class/:checked, y el click escribe
       esa expresión DIRECTO como directiva (`@click="expr = !expr"`), no dentro
       de un método — un método de x-data es una función JS normal creada una
       sola vez y closure-cerrada en su propio scope; solo las expresiones de
       directivas (@click, :class, etc.) se re-evalúan cada vez con `with(scope)`
       resolviendo contra el x-data ancestro. `this.on = ...` dentro de un método
       SÍ funciona porque "on" es una propiedad del propio objeto reactivo; un
       identificador libre como "isMobile" (ajeno) dentro de ese mismo método NO
       resuelve al padre — por eso los dos casos no pueden compartir un toggle().
    2. `wire:model` — @entangle sobre la propiedad "on" propia del componente.
    3. Ninguno de los dos — estado interno aislado "on", arranca en `checked`.
--}}
@php
    $wireModel = $attributes->wire('model');
    $xModel = $attributes->get('x-model');
    $stateExpr = $xModel ?: 'on';
    $clickExpr = $xModel
        ? ($disabled ? '' : "{$xModel} = !{$xModel}")
        : 'toggle()';
@endphp

<div
    x-data="{
        @unless($xModel)
            on: @if($wireModel->value())
                    @entangle($wireModel)
                @else
                    {{ $checked ? 'true' : 'false' }}
                @endif,

            toggle() {
                if ({{ $disabled ? 'true' : 'false' }}) return;
                this.on = !this.on;
            }
        @endunless
    }"
    class="flex items-center justify-between gap-4 {{ $disabled ? 'opacity-50' : '' }}"
>
    {{-- Label y descripción --}}
    <div class="flex flex-col min-w-0">
        @if ($label)
            <span class="text-sm font-semibold text-slate-700 leading-snug select-none break-words">
                {{ $label }}
            </span>
        @endif
        @if ($description)
            <span class="text-xs text-slate-400 mt-0.5 leading-snug select-none break-words">
                {{ $description }}
            </span>
        @endif
    </div>

    {{-- Toggle visual --}}
    <div class="relative flex-shrink-0">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            :checked="{{ $stateExpr }}"
            class="sr-only"
            @disabled($disabled)
            {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model']) }}
        />

        {{-- Pista del toggle --}}
        <button
            type="button"
            role="switch"
            :aria-checked="{{ $stateExpr }}"
            @click="{{ $clickExpr }}"
            :class="{{ $stateExpr }}
                ? 'bg-zertix-primary shadow-zertix-primary/30 shadow-md'
                : 'bg-slate-200'"
            class="relative inline-flex h-6 w-11 items-center rounded-full
                   transition-all duration-200 focus:outline-none
                   focus-visible:ring-2 focus-visible:ring-zertix-primary focus-visible:ring-offset-2
                   {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}"
            {{-- Attrs no relacionados a wire:model/x-model (ej. wire:click de un
                 padre que controla el estado por acción en vez de wire:model)
                 también se fusionan aquí en el botón — es el único elemento que
                 el usuario realmente clickea, el <input> de abajo es solo su
                 reflejo oculto. --}}
            {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->whereDoesntStartWith(['name', 'id', 'value']) }}
        >
            {{-- Bolita --}}
            <span
                :class="{{ $stateExpr }} ? 'translate-x-6' : 'translate-x-1'"
                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm
                       transition-transform duration-200"
            ></span>
        </button>
    </div>
</div>
