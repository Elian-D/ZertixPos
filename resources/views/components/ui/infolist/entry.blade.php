{{--
    x-ui.infolist.entry — par etiqueta/valor dentro de una sección o fila-tarjeta.

    PROPS:
      label   — etiqueta (obligatoria).
      value   — valor en texto plano. Si se pasa contenido en el slot, el slot gana
                (badges, enlaces, montos con formato).
      strong  — dato principal: semibold oscuro.
      full    — ocupa toda la fila de la grilla (notas, direcciones largas).
      href    — el valor es un enlace (a otro show, tel:, mailto:).

    Vacío (value null/'' y slot vacío) → "—" en gris, siempre igual en todo el sistema.
--}}
@props([
    'label',
    'value' => null,
    'strong' => false,
    'full' => false,
    'href' => null,
])

@php
    $hasSlot = trim((string) $slot) !== '';
    $isEmpty = ! $hasSlot && ($value === null || $value === '');
@endphp

<div {{ $attributes->class(['min-w-0', 'sm:col-span-full' => $full]) }}>
    <dt class="text-sm font-medium text-gray-900">{{ $label }}</dt>
    <dd @class([
        'mt-1.5 text-sm break-words',
        'font-semibold text-gray-900' => $strong,
        'text-gray-600' => ! $strong,
    ])>
        @if($isEmpty)
            <span class="text-gray-400">—</span>
        @elseif($href)
            <a href="{{ $href }}" class="text-zertix-primary-700 hover:underline">{{ $hasSlot ? $slot : $value }}</a>
        @else
            {{ $hasSlot ? $slot : $value }}
        @endif
    </dd>
</div>
