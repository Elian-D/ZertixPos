{{--
    x-ui.report.filters — barra de filtros de un reporte (v1.5.0 Fase 6): tarjeta blanca con
    un <form method="GET"> que se envía solo al cambiar cualquier campo. Ver docs/ui/reports.md.

    PROPS:
      action — URL del reporte (por defecto la actual, sin query).
      cols   — columnas en desktop (default 3).

    SLOT: los campos (x-ui.forms.input type="date", x-ui.forms.select…), cada uno con su name.
--}}
@props(['action' => null, 'cols' => 3])

@php
    $grid = [2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-2 lg:grid-cols-4'][(int) $cols] ?? 'sm:grid-cols-3';
@endphp

<form method="GET" action="{{ $action ?? url()->current() }}" x-data x-on:change="$el.requestSubmit()"
    {{ $attributes->class('bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4') }}>
    <div class="grid grid-cols-1 {{ $grid }} gap-4">
        {{ $slot }}
    </div>
</form>
