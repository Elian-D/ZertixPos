{{--
    x-ui.infolist.repeatable — contenedor de filas-tarjeta (RepeatableEntry de
    Filament): líneas de un documento o registros relacionados. Cada fila es un
    x-ui.infolist.repeatable-item.

    PROPS:
      empty            — true si no hay elementos: muestra el empty-state.
      emptyTitle       — título del empty-state.
      emptyDescription — descripción del empty-state (opcional).
      emptyIcon        — heroicon del empty-state.
--}}
@props([
    'empty' => false,
    'emptyTitle' => 'Sin registros',
    'emptyDescription' => null,
    'emptyIcon' => 'heroicon-o-inbox',
])

<div {{ $attributes->class(['space-y-3']) }}>
    @if($empty)
        <x-ui.empty-state variant="simple" :icon="$emptyIcon" :title="$emptyTitle" :description="$emptyDescription" />
    @else
        {{ $slot }}
    @endif
</div>
