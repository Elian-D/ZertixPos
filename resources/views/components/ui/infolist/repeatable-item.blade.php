{{--
    x-ui.infolist.repeatable-item — una fila-tarjeta: grilla de x-ui.infolist.entry
    con sus propias etiquetas. En móvil las columnas se apilan de a 2.

    PROPS:
      cols — columnas en desktop: 3|4|5|6|7 (default 6).
--}}
@props(['cols' => 6])

@php
    $grid = [
        3 => 'sm:grid-cols-3',
        4 => 'sm:grid-cols-4',
        5 => 'sm:grid-cols-5',
        6 => 'sm:grid-cols-6',
        7 => 'sm:grid-cols-7',
    ][$cols] ?? 'sm:grid-cols-6';
@endphp

<dl {{ $attributes->class(['rounded-xl border border-gray-100 shadow-sm px-4 py-4 grid grid-cols-2 gap-x-4 gap-y-5', $grid]) }}>
    {{ $slot }}
</dl>
