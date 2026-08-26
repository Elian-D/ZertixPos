@props([
    'column',
    'visible' => [],
    'class'   => 'px-4 py-3.5',
])

@if(in_array($column, $visible))
    <td {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </td>
@endif
