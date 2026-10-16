{{--
    x-ui.report.progress — barra horizontal de proporción (v1.5.0 Fase 6): participación de un
    método de pago, uso de una secuencia NCF… Ver docs/ui/reports.md.

    PROPS:
      value   — 0 a 100.
      variant — primary | success | warning | error (default primary).
--}}
@props(['value' => 0, 'variant' => 'primary'])

@php
    $color = [
        'success' => 'bg-state-success',
        'warning' => 'bg-state-warning',
        'error' => 'bg-state-error',
    ][$variant] ?? 'bg-zertix-primary';
@endphp

<div {{ $attributes->class('h-1.5 w-full rounded-full bg-gray-100 overflow-hidden') }}>
    <div class="h-full rounded-full {{ $color }}" style="width: {{ max(0, min(100, (float) $value)) }}%"></div>
</div>
