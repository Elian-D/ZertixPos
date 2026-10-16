{{--
    x-ui.infolist.repeater-item — una línea de x-ui.infolist.repeater: tarjeta gris
    (contraste con el fondo blanco de la sección).

    Dos formas:
      - Normal: encabezado (título/resumen + quitar + chevron) y los campos debajo; se
        contrae y expande. Para líneas con varios campos (ajuste de inventario).
      - compact: una sola fila, sin contraer — el título a la izquierda y los campos a la
        derecha. Para listas largas de una o dos cantidades por línea (toma física, con
        cientos de productos), donde una tarjeta por producto haría la página eterna.

    PROPS:
      title          — título o resumen fijo de la línea (ej. 'Nueva línea').
      compact        — una sola fila, sin contraer.
      removeAction   — expresión Alpine del botón de quitar (ej. 'removeLine(i)'). Sin ella
                       no hay botón.
      removeDisabled — expresión Alpine que deshabilita el botón (ej. 'lines.length === 1').
      collapsed      — arranca contraída (solo la forma normal).

    SLOTS:
      $title — resumen dinámico en lugar de `title`, cuando se puede resumir la línea
               (ej. <span x-text="summary(line)"></span>). Se ve también contraída.
      $slot  — los campos de la línea.

    Funciona igual en un @foreach de Blade que dentro de un <template x-for> de Alpine
    (su estado `open` es local a cada línea).
--}}
@props([
    'title' => null,
    'compact' => false,
    'removeAction' => null,
    'removeDisabled' => null,
    'collapsed' => false,
])

@if($compact)
    <div {{ $attributes->class(['rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 flex flex-col sm:flex-row sm:items-center gap-x-4 gap-y-2']) }}
        data-repeater-item>
        {{-- Un <x-slot:title> reemplaza al prop `title` del mismo nombre: sirve para los dos. --}}
        <div class="min-w-0 flex-1 text-sm text-gray-800">{{ $title }}</div>

        <div class="flex items-center gap-3 shrink-0">
            {{ $slot }}

            @if($removeAction)
                <x-ui.button type="button" variant="error" appearance="ghost" size="sm" icon="heroicon-s-trash"
                    x-on:click.stop="{{ $removeAction }}"
                    :x-bind:disabled="$removeDisabled"
                    aria-label="Quitar línea" title="Quitar línea" />
            @endif
        </div>
    </div>
@else
    {{-- Acordeón: abrir esta línea (o agregarla) cierra las demás del mismo repeater, para
         dejar espacio. "Expandir todo" sí las abre todas. --}}
    <div {{ $attributes->class(['rounded-xl border border-gray-200 bg-gray-50']) }}
        x-data="{
            open: @js(! $collapsed),
            toggle() {
                this.open = ! this.open;
                if (this.open) this.closeSiblings();
            },
            closeSiblings() {
                const repeater = this.$root.closest('[data-repeater]');
                repeater?.querySelectorAll('[data-repeater-item]').forEach(el => {
                    if (el !== this.$root && el.closest('[data-repeater]') === repeater) {
                        el.dispatchEvent(new CustomEvent('repeater-set', { detail: false }));
                    }
                });
            },
        }"
        x-init="setTimeout(() => { if (open && $root.closest('[data-repeater]')?.dataset.ready) closeSiblings() }, 0)"
        x-on:repeater-set="open = $event.detail"
        data-repeater-item>

        <div class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none" @click="toggle()">
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
@endif
