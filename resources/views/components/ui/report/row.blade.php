{{--
    x-ui.report.row — una fila de x-ui.report.rows: etiqueta a la izquierda y valor a la derecha
    (v1.5.0 Fase 6). Ver docs/ui/reports.md.

    PROPS:
      label  — texto principal (obligatorio).
      sub    — línea gris debajo de la etiqueta (opcional).
      value  — valor ya formateado a la derecha (opcional; el slot lo reemplaza).
      hint   — texto gris pequeño antes del valor (ej. "(2)", "6 / 10").
      href   — si se pasa, la etiqueta es un enlace.
      strong — valor en negrita (default true).
      variant — default | error | success | warning: color del valor.

    SLOTS:
      $badge — badge entre la etiqueta y el valor (ej. "Agotado").
      $slot  — reemplaza a value cuando hace falta markup.
--}}
@props([
    'label',
    'sub' => null,
    'value' => null,
    'hint' => null,
    'href' => null,
    'strong' => true,
    'variant' => 'default',
])

@php
    $color = [
        'error' => 'text-state-error',
        'success' => 'text-state-success',
        'warning' => 'text-state-warning',
    ][$variant] ?? 'text-gray-900';
@endphp

<li {{ $attributes->class('flex items-center gap-3 py-2.5') }}>
    <div class="min-w-0 flex-1">
        @if($href)
            <a href="{{ $href }}" class="block text-sm text-gray-800 hover:text-zertix-primary-700 hover:underline truncate">{{ $label }}</a>
        @else
            <span class="block text-sm text-gray-800 truncate">{{ $label }}</span>
        @endif
        @if($sub)
            <span class="block text-xs text-slate-500 truncate">{{ $sub }}</span>
        @endif
    </div>
    @isset($badge)
        <span class="shrink-0">{{ $badge }}</span>
    @endisset
    @if($hint)
        <span class="shrink-0 text-xs text-slate-400 tabular-nums">{{ $hint }}</span>
    @endif
    <span @class(['shrink-0 text-sm tabular-nums text-right', $color, 'font-semibold' => $strong])>
        {{ $slot->isNotEmpty() ? $slot : $value }}
    </span>
</li>
