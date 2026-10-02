{{--
    x-pdf.totals — bloque de totales alineado a la derecha; el slot ocupa la
    columna izquierda (detalle de pago, firma, avisos).

    PROPS:
      rows  — [['label' => 'Subtotal', 'value' => 'RD$100.00', 'variant' => null|'discount'|'grand'|'sep'], ...]
              'sep' deja aire antes de la fila (separa grupos sin otra línea).
              Filas sin 'value' se omiten.
      width — ancho del bloque de totales (default 42%).
--}}
@props(['rows' => [], 'width' => 42])

<table class="w-full" style="margin-top: 14px;">
    <tr>
        <td style="width: {{ 100 - $width }}%; vertical-align: top; padding-right: 20px;">{{ $slot }}</td>
        <td style="width: {{ $width }}%; vertical-align: top;">
            <table class="totals">
                @foreach($rows as $row)
                    @continue(! filled($row['value'] ?? null))
                    @if(($row['variant'] ?? null) === 'grand')
                        {{-- La línea del total va en su propia fila de una sola celda:
                             como border-top de dos celdas, DomPDF la dibuja con un
                             escalón en la unión. --}}
                        <tr><td colspan="2" class="grand-rule"></td></tr>
                    @endif
                    <tr class="{{ $row['variant'] ?? '' }}">
                        <td class="t-label {{ ($row['variant'] ?? null) === 'discount' ? 'discount' : '' }}">{{ $row['label'] }}</td>
                        <td class="num {{ ($row['variant'] ?? null) === 'discount' ? 'discount' : 'bold' }}">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
