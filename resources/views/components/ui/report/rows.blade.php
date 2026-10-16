{{--
    x-ui.report.rows — lista de filas de un panel de reporte (v1.5.0 Fase 6), separadas por
    una línea. Cada fila es un x-ui.report.row. Ver docs/ui/reports.md.

    PROPS:
      empty — texto gris cuando no hay filas (con él, $slot vacío muestra el texto).

    SLOT: las filas.
--}}
@props(['empty' => null])

@if($slot->isEmpty() && $empty)
    <p class="py-6 text-center text-sm text-slate-400">{{ $empty }}</p>
@else
    <ul {{ $attributes->class('divide-y divide-gray-100 -my-2') }}>
        {{ $slot }}
    </ul>
@endif
