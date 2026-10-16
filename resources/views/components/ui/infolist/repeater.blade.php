{{--
    x-ui.infolist.repeater — lista de líneas editables (Repeater de Filament), para las
    líneas de un formulario: ajuste de inventario, y más adelante compras, mermas,
    transferencias. Cada línea es un x-ui.infolist.repeater-item. Ver docs/ui/infolist.md.

    PROPS:
      addLabel   — texto del botón de agregar (default 'Agregar línea').
      addAction  — expresión Alpine del botón (ej. 'addLine()'). Sin ella no hay botón.
                   Siempre va DEBAJO de la última línea: se agrega sin volver a subir.
      count      — número de líneas cuando se renderizan con Blade (@foreach).
      countExpr  — lo mismo como expresión Alpine cuando las líneas salen de un x-for
                   (ej. 'lines.length'). "Contraer todo / Expandir todo" solo aparece con
                   más de una línea.

    Contraer/expandir todo avisa solo a SUS líneas (evento 'repeater-set' a cada
    [data-repeater-item] que le pertenece), así dos repeaters en la misma página no se
    pisan.
--}}
@props([
    'addLabel' => 'Agregar línea',
    'addAction' => null,
    'count' => null,
    'countExpr' => null,
])

<div {{ $attributes->class(['flex flex-col gap-3']) }}
    x-data="{
        setAll(open) {
            this.$root.querySelectorAll('[data-repeater-item]').forEach(el => {
                if (el.closest('[data-repeater]') === this.$root) {
                    el.dispatchEvent(new CustomEvent('repeater-set', { detail: open }));
                }
            });
        },
    }"
    {{-- Listo tras el primer render: desde aquí, una línea nueva abre cerrando las demás
         (acordeón). Las líneas que llegan al cargar, como un old() con errores, no se cierran. --}}
    x-init="setTimeout(() => { $el.dataset.ready = '1' }, 50)"
    data-repeater>

    {{-- Contraer / expandir todo (más de una línea) --}}
    @if($countExpr)
        <div class="flex items-center gap-4 text-sm" x-show="{{ $countExpr }} > 1" x-cloak>
            <button type="button" class="font-medium text-slate-500 hover:text-slate-800" @click="setAll(false)">Contraer todo</button>
            <button type="button" class="font-medium text-slate-500 hover:text-slate-800" @click="setAll(true)">Expandir todo</button>
        </div>
    @elseif((int) $count > 1)
        <div class="flex items-center gap-4 text-sm">
            <button type="button" class="font-medium text-slate-500 hover:text-slate-800" @click="setAll(false)">Contraer todo</button>
            <button type="button" class="font-medium text-slate-500 hover:text-slate-800" @click="setAll(true)">Expandir todo</button>
        </div>
    @endif

    <div class="flex flex-col gap-2">
        {{ $slot }}
    </div>

    @if($addAction)
        <div class="flex justify-center pt-1">
            <x-ui.button type="button" variant="secondary" appearance="outline" size="sm" iconLeft="heroicon-s-plus"
                x-on:click="{{ $addAction }}">
                {{ $addLabel }}
            </x-ui.button>
        </div>
    @endif
</div>
