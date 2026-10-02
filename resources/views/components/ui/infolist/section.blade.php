{{--
    x-ui.infolist.section — tarjeta de sección de una vista show (patrón Infolist,
    ver docs/ui/infolist.md y el skill /filament-show).

    PROPS:
      title        — título en tipo oración (obligatorio).
      icon         — heroicon outline por tema real (ej. 'heroicon-o-identification').
      description  — texto corto bajo el título (opcional).
      cols         — 1|2|3|4: el slot es una grilla <dl> de entradas con esas columnas.
                     0: el slot se renderiza tal cual con padding (listas, filas-tarjeta).
                     (no null: Blade trata un prop null como "no pasado" y usaría el default).
      collapsible  — encabezado clickeable con chevron (Alpine local).
      collapsed    — arranca cerrada (solo con collapsible).
      flat         — sin borde/sombra propios: para secciones dentro de otra tarjeta (tabs).

    SLOTS:
      $slot        — entradas (x-ui.infolist.entry) o contenido libre.
      $headerActions — botones a la derecha del título (opcional).
--}}
@props([
    'title',
    'icon' => null,
    'description' => null,
    'cols' => 4,
    'collapsible' => false,
    'collapsed' => false,
    'flat' => false,
])

@php
    $grid = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        // 3 y 4 columnas recién desde xl (v1.4.0 REQ-3.20): en lg, con la barra
        // lateral abierta, cada columna quedaba en ~150px y los badges se partían.
        3 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
    ][(int) $cols] ?? null;
@endphp

<section {{ $attributes->class([
    'bg-white rounded-2xl',
    'border border-gray-100 shadow-sm' => ! $flat,
    'border border-gray-100' => $flat,
]) }}
    @if($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif>

    <div @class([
            'flex items-center gap-2 px-5 sm:px-6 py-4',
            'cursor-pointer select-none' => $collapsible,
            'border-b border-gray-100' => ! $collapsible,
        ])
        @if($collapsible) @click="open = !open" :class="open && 'border-b border-gray-100'" @endif>
        @if($icon)
            <x-dynamic-component :component="$icon" class="w-5 h-5 text-slate-400 shrink-0" />
        @endif
        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
            @if($description)
                <p class="text-xs text-slate-500 mt-0.5">{{ $description }}</p>
            @endif
        </div>
        @isset($headerActions)
            <div class="flex items-center gap-2" @if($collapsible) @click.stop @endif>{{ $headerActions }}</div>
        @endisset
        @if($collapsible)
            <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
        @endif
    </div>

    <div @if($collapsible) x-show="open" x-collapse @endif>
        @if($grid)
            <dl class="p-5 sm:p-6 grid {{ $grid }} gap-x-6 gap-y-7">{{ $slot }}</dl>
        @else
            <div class="p-5 sm:p-6">{{ $slot }}</div>
        @endif
    </div>
</section>
