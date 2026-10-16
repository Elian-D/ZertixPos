{{--
    x-ui.report.bar-chart — gráfica de barras verticales hecha con Tailwind, sin librería
    (v1.5.0 Fase 6). Cada barra muestra su valor al pasar el mouse. Ver docs/ui/reports.md.

    PROPS:
      bars   — [['label' => '15', 'value' => 1200.5, 'tooltip' => '15 oct · RD$1,200.50'], ...].
               'tooltip' es opcional (por defecto: label · value).
      height — alto del área en px (default 140).
      labelEvery — muestra la etiqueta cada N barras (default: automático para ~15 etiquetas).
      highlight — índice de la barra resaltada (ej. hoy), opcional.
      empty  — texto cuando todas las barras están en 0.
--}}
@props([
    'bars' => [],
    'height' => 140,
    'labelEvery' => null,
    'highlight' => null,
    'empty' => 'Sin datos en el período.',
])

@php
    $bars = array_values($bars);
    $max = max(array_merge([0], array_map(fn ($b) => (float) $b['value'], $bars)));
    $every = $labelEvery ?? max(1, (int) ceil(count($bars) / 15));
@endphp

@if($max <= 0)
    <p class="py-10 text-center text-sm text-slate-400">{{ $empty }}</p>
@else
    <div {{ $attributes->class('w-full') }}>
        <div class="flex items-end gap-[3px]" style="height: {{ (int) $height }}px">
            @foreach($bars as $i => $bar)
                @php $pct = $max > 0 ? max(((float) $bar['value'] / $max) * 100, (float) $bar['value'] > 0 ? 2 : 0.5) : 0; @endphp
                <div class="group relative flex-1 h-full flex items-end min-w-0">
                    <div @class([
                            'w-full rounded-t transition-colors',
                            'bg-zertix-primary' => $i === $highlight,
                            'bg-zertix-primary/40 group-hover:bg-zertix-primary/70' => $i !== $highlight && (float) $bar['value'] > 0,
                            'bg-gray-100' => (float) $bar['value'] <= 0,
                        ]) style="height: {{ $pct }}%"></div>
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] text-white z-10">
                        {{ $bar['tooltip'] ?? ($bar['label'].' · '.$bar['value']) }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="mt-1.5 flex gap-[3px]">
            @foreach($bars as $i => $bar)
                <span class="flex-1 min-w-0 text-center text-[10px] text-slate-400 tabular-nums">{{ $i % $every === 0 ? $bar['label'] : '' }}</span>
            @endforeach
        </div>
    </div>
@endif
