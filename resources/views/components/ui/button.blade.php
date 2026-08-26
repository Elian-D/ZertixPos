@php
    $isIconOnly = empty(trim($slot->toHtml())) && ($icon || $iconLeft || $iconRight);
    $finalIcon  = $icon ?? $iconLeft ?? $iconRight;
    $tag        = $tag();  // 'button' o 'a'
    $isSubmit   = $tag === 'button' && $attributes->get('type', 'button') === 'submit';

    $iconSize = match($size) {
        'sm'    => 'w-4 h-4',
        'xl'    => 'w-7 h-7',
        default => 'w-5 h-5',
    };
    $spinnerSize = match($size) {
        'sm'    => 'xs',
        'xl'    => 'lg',
        default => 'sm',
    };

    // Estilos inline para hex — vacío si no hay hex
    $hexStyle = $hexStyles();
@endphp

{{--
    Nota de implementación:
    ─────────────────────────────────────────────────────────────────
    - $tag()      → 'button' si no hay href, 'a' si hay href
    - $hexStyles() → estilos inline calculados si se pasa prop hex
    - Guard de doble-submit (solo en <button type="submit">): escucha el evento
      nativo "submit" del <form> más cercano (NO el "click" del botón) y recién
      ahí entra en "sending" — el ícono (o el ícono-único en modo solo-ícono) se
      reemplaza por un spinner mientras dura el request; el texto del slot NO
      cambia (estilo Filament, no "Guardando..." — evita que el botón cambie de
      ancho). El navegador solo dispara "submit" después de pasar su propia
      validación HTML5, así que no hace falta llamar reportValidity() a mano.
      Importante: deshabilitar el botón (:disabled="sending") DENTRO de su propio
      handler de click, antes de que el navegador complete la acción nativa de
      envío, puede cancelar ese envío en algunos navegadores (gotcha conocido de
      "disable submit button onclick") — por eso el guard reacciona al evento
      "submit" del form, que se dispara cuando el navegador ya está comprometido
      a enviar, y deshabilitar el botón después ya no puede cancelarlo.
    - El feedback de wire:loading NO se aplica por defecto (ver REQ-11.1) —
      cada botón Livewire debe declarar explícitamente wire:loading + wire:target
      para evitar que se ilumine ante cualquier acción de la página, no solo la suya.
    - aria-label obligatorio en modo icono para accesibilidad — si no
      se pasa el atributo aria-label externamente, se infiere del componente
      del ícono como fallback.
--}}

<{{ $tag }}
    @if($isSubmit)
        x-data="{ sending: false }"
        x-init="$el.closest('form')?.addEventListener('submit', () => { sending = true })"
    @endif
    @if($tag === 'button')
        type="{{ $attributes->get('type', 'button') }}"
        @if($isSubmit)
            :disabled="sending || {{ $disabled ? 'true' : 'false' }}{{ $disabledWhen ? " || ($disabledWhen)" : '' }}"
        @else
            {{ $disabled ? 'disabled' : '' }}
        @endif
    @else
        href="{{ $href }}"
        @if($disabled) aria-disabled="true" tabindex="-1" @endif
    @endif
    @if($isIconOnly && !$attributes->has('aria-label') && $finalIcon)
        aria-label="{{ str_replace(['heroicon-s-', 'heroicon-o-'], '', $finalIcon) }}"
    @endif
    {{ $attributes->except(['type', 'href'])->merge([
        'class' => $getButtonClasses($isIconOnly),
        'style' => $hexStyle,
    ]) }}
>
    @if($isIconOnly)
        {{-- Modo solo icono — cuadrado automático --}}
        @if($isSubmit)
            <span x-show="sending" x-cloak class="inline-flex">
                <x-ui.loading size="{{ $spinnerSize }}" />
            </span>
            <span x-show="!sending" class="inline-flex">
                <x-dynamic-component :component="$finalIcon" class="{{ $iconSize }} flex-shrink-0" aria-hidden="true" />
            </span>
        @else
            <x-dynamic-component :component="$finalIcon" class="{{ $iconSize }} flex-shrink-0" aria-hidden="true" />
        @endif

    @else
        {{-- Modo con texto — icono izquierdo (o spinner mientras envía) + slot + icono derecho --}}
        @if($isSubmit)
            <span x-show="sending" x-cloak class="inline-flex">
                <x-ui.loading size="{{ $spinnerSize }}" />
            </span>
            @if($iconLeft)
                <span x-show="!sending" class="inline-flex">
                    <x-dynamic-component :component="$iconLeft" class="{{ $iconSize }} flex-shrink-0" aria-hidden="true" />
                </span>
            @endif
        @elseif($iconLeft)
            <x-dynamic-component :component="$iconLeft" class="{{ $iconSize }} flex-shrink-0" aria-hidden="true" />
        @endif

        {{-- span limpio sin espacios extra que romperían la detección de isIconOnly --}}
        <span>{{ $slot }}</span>

        @if($iconRight)
            <x-dynamic-component :component="$iconRight" class="{{ $iconSize }} flex-shrink-0" aria-hidden="true" />
        @endif
    @endif

</{{ $tag }}>
