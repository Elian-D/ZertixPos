{{--
    x-pdf.meta — caja gris de datos en grilla (etiqueta arriba, valor abajo).

    PROPS:
      items — [['label' => 'Cliente', 'value' => '...', 'span' => 2, 'strong' => true, 'sub' => 'línea gris'], ...]
              Un item sin valor (null/'') se omite entero, etiqueta incluida.
              'value' se imprime escapado; para HTML propio (un badge) usar 'html'.
      cols  — columnas de la grilla (default 4).
--}}
@props(['items' => [], 'cols' => 4])

@php
    $items = collect($items)->filter(fn ($i) => filled($i['value'] ?? null) || filled($i['html'] ?? null))->values();

    // Arma filas respetando 'span': un item que no cabe en la fila actual abre una nueva.
    $rows = [];
    $row = [];
    $used = 0;
    foreach ($items as $item) {
        $span = min($cols, $item['span'] ?? 1);
        if ($used + $span > $cols) {
            $rows[] = [$row, $used];
            $row = [];
            $used = 0;
        }
        $row[] = $item + ['span' => $span];
        $used += $span;
    }
    if ($row) {
        $rows[] = [$row, $used];
    }
    $colWidth = 100 / $cols;
@endphp

@if($items->isNotEmpty())
    <table class="meta-box">
        @foreach($rows as [$cells, $usedCols])
            <tr>
                @foreach($cells as $cell)
                    <td colspan="{{ $cell['span'] }}" style="width: {{ $colWidth * $cell['span'] }}%;">
                        <span class="label">{{ $cell['label'] }}</span>
                        <span class="value {{ ($cell['strong'] ?? false) ? 'bold' : '' }}">
                            @if(filled($cell['html'] ?? null)){!! $cell['html'] !!}@else{{ $cell['value'] }}@endif
                        </span>
                        @if(filled($cell['sub'] ?? null))
                            <span class="cell-sub">{{ $cell['sub'] }}</span>
                        @endif
                    </td>
                @endforeach
                @if($usedCols < $cols)
                    <td colspan="{{ $cols - $usedCols }}"></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif
