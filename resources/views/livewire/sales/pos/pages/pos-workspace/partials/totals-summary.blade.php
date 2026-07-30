{{--
    Resumen de totales del carrito. Compartido entre el panel de escritorio y la
    pestaña "Cobrar" del slide-over móvil (Fase 7.10) — antes pegado dos veces.

    Props:
    - detailed (bool): false (escritorio) = solo Subtotal + Total, compacto, porque
      el desglose completo (descuento/ITBIS) ya se repite en el modal de cobro que
      se abre justo después. true (móvil) = incluye Descuento e ITBIS inline, porque
      en la pestaña "Cobrar" esta es la única vista del desglose antes de tocar
      "Confirmar Cobro" — no hay una barra lateral fija donde ya se haya visto.
--}}
@php $detailed = $detailed ?? false; @endphp
@if($detailed)
    <div class="bg-white border border-gray-200 rounded-xl p-3 space-y-1.5">
        <div class="flex justify-between text-xs text-gray-500">
            <span>Subtotal</span><span x-text="formatMoney(totals.gross)"></span>
        </div>
        <template x-if="totals.discount > 0">
            <div class="flex justify-between text-xs text-red-500">
                <span>Descuento</span><span x-text="'- ' + formatMoney(totals.discount)"></span>
            </div>
        </template>
        <template x-if="usaNcf">
            <div class="flex justify-between text-xs text-gray-500">
                <span>ITBIS</span><span x-text="formatMoney(totals.tax)"></span>
            </div>
        </template>
        <div class="pt-1.5 border-t border-dashed border-gray-200 flex justify-between items-center">
            <span class="font-bold text-gray-700">Total</span>
            <span class="text-xl font-black font-mono text-[#58c03f]" x-text="formatMoney(totals.total)"></span>
        </div>
    </div>
@else
    <div class="pt-1.5 space-y-0.5 border-t border-gray-100">
        <div class="flex justify-between text-[11px] text-gray-500">
            <span>Subtotal</span><span x-text="formatMoney(totals.gross)"></span>
        </div>
        <div class="flex justify-between items-end pt-0.5">
            <span class="text-[10px] font-bold text-[#58c03f] uppercase tracking-widest">Total</span>
            <span class="text-xl font-black font-mono text-gray-900" x-text="formatMoney(totals.total)"></span>
        </div>
    </div>
@endif
