{{--
    x-ui.infolist.repeater-item — una línea de x-ui.infolist.repeater: tarjeta gris
    (contraste con el fondo blanco de la sección) que se contrae y expande.

    PROPS:
      title          — título o resumen fijo de la línea (ej. 'Nueva línea').
      removeAction   — expresión Alpine del botón de quitar (ej. 'removeLine(i)'). Sin ella
                       no hay botón.
      removeDisabled — expresión Alpine que deshabilita el botón (ej. 'lines.length === 1').
      collapsed      — arranca contraída.

    SLOTS:
      $title — resumen dinámico en lugar de `title`, cuando se puede resumir la línea
               (ej. <span x-text="summary(line)"></span>). Se ve también contraída.
      $slot  — los campos de la línea.

    Clic en el encabezado (fuera del botón de quitar) contrae/expande. Funciona igual en
    un @foreach de Blade que dentro de un <template x-for> de Alpine (su estado `open`
    es local a cada línea).
--}}
@props([
    'title' => null,
    'removeAction' => null,
    'removeDisabled' => null,
    'collapsed' => false,
])

<div {{ $attributes->class(['rounded-xl border border-gray-200 bg-gray-50']) }}
    x-data="{ open: @js(! $collapsed) }"
    x-on:repeater-set="open = $event.detail"
    data-repeater-item>

    <div class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none" @click="open = !open">
        {{-- Un <x-slot:title> reemplaza al prop `title` del mismo nombre: sirve para los dos. --}}
        <div class="min-w-0 flex-1 truncate text-sm font-medium text-gray-800">{{ $title }}</div>

        @if($removeAction)
            <x-ui.button type="button" variant="error" appearance="ghost" size="sm" icon="heroicon-s-trash"
                x-on:click.stop="{{ $removeAction }}"
                :x-bind:disabled="$removeDisabled"
                aria-label="Quitar línea" title="Quitar línea" />
        @endif

        <x-heroicon-s-chevron-down class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
            x-bind:class="open && 'rotate-180'" />
    </div>

    <div x-show="open" x-transition.opacity class="border-t border-gray-200 px-4 py-4">
        {{ $slot }}
    </div>
</div>
