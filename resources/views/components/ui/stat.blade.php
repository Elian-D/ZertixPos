{{--
    x-ui.stat — tarjeta de un total grande (v1.5.0 Fase 3): "Total disponible", "En cajas".
    Pensada también para los tableros de la Fase 6. Ver docs/ui/infolist.md.

    PROPS:
      label    — etiqueta corta arriba (se muestra en mayúsculas pequeñas).
      value    — el número ya formateado (ej. 'RD$486,320.50').
      hint     — línea chica debajo (opcional).
      hintIcon — heroicon antes del hint (opcional).
      variant  — default | error | success (color del valor; default gris oscuro).
      href     — si se pasa, la tarjeta entera es un enlace.

    SLOT (opcional): reemplaza al hint cuando hace falta markup (ej. un badge).
--}}
@props([
    'label',
    'value',
    'hint' => null,
    'hintIcon' => null,
    'variant' => 'default',
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
    $valueColor = [
        'error' => 'text-state-error',
        'success' => 'text-state-success',
    ][$variant] ?? 'text-slate-900';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'block bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 min-w-0',
        'hover:border-gray-200 transition-colors' => $href,
    ]) }}>
    <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</span>
    <span class="mt-1 block text-2xl font-bold tabular-nums truncate {{ $valueColor }}">{{ $value }}</span>
    @if($slot->isNotEmpty())
        <div class="mt-1.5 text-xs text-slate-500">{{ $slot }}</div>
    @elseif($hint)
        <span class="mt-1.5 flex items-center gap-1 text-xs text-slate-500">
            @if($hintIcon)
                <x-dynamic-component :component="$hintIcon" class="w-3.5 h-3.5 shrink-0" />
            @endif
            {{ $hint }}
        </span>
    @endif
</{{ $tag }}>
