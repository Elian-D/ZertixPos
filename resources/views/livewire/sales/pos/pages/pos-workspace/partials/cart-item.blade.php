{{--
    Fila individual del carrito (nombre, stepper de cantidad, descuento por ítem,
    subtotal de línea, aviso de stock insuficiente). Compartida por el panel de
    escritorio y la pestaña "Productos" del slide-over móvil — antes estaba pegada
    dos veces casi idéntica, y de ahí salió el bug del input de descuento faltante
    en móvil (Fase 7.10).

    Debe incluirse DENTRO de un <template x-for="(item, index) in items">; usa
    directamente `item`/`index` del scope de Alpine del padre.

    Props:
    - touch (bool): true = variante táctil (botones +/- más grandes, cantidad de
      solo lectura); false = variante mouse/teclado (botones compactos, cantidad
      editable con input numérico). Táctil evita depender de tocar exactamente
      encima de un input pequeño para cambiar la cantidad.
--}}
@php $qtyBtn = ($touch ?? false) ? 'w-8 h-8' : 'w-6 h-7'; @endphp
<div class="py-3" :class="inventoryTrackingEnabled && item.is_stockable && item.quantity > item.stock ? 'bg-red-50/50 -mx-4 px-4 rounded-lg' : ''">
    <div class="flex justify-between items-start gap-2">
        <div class="min-w-0">
            <p class="text-sm font-bold text-gray-800 truncate" x-text="item.name"></p>
            <p class="text-[11px] text-gray-400" x-text="formatMoney(item.price) + ' c/u'"></p>
        </div>
        <button type="button" @click="removeItem(index)" class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 transition-colors">
            <x-heroicon-s-x-mark class="w-4 h-4" />
        </button>
    </div>

    <div class="flex items-center gap-2 mt-2">
        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden shrink-0">
            <button type="button" @click="decrementQty(index)"
                    class="{{ $qtyBtn }} flex items-center justify-center text-gray-500 active:bg-gray-200 hover:bg-gray-100 transition-colors">
                <span class="text-sm font-bold leading-none">&minus;</span>
            </button>
            @if($touch ?? false)
                <span class="w-9 text-center text-xs font-bold" x-text="item.quantity"></span>
            @else
                <input type="number" min="1" x-model.number="item.quantity" @input="recalculateTotals()"
                       class="w-14 text-center border-0 border-x border-gray-200 text-xs py-1.5 px-0 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
            @endif
            <button type="button" @click="incrementQty(index)"
                    class="{{ $qtyBtn }} flex items-center justify-center text-gray-500 active:bg-gray-200 hover:bg-gray-100 transition-colors">
                <span class="text-sm font-bold leading-none">+</span>
            </button>
        </div>

        <template x-if="allowItemDiscount">
            <div class="relative flex-1">
                <input type="number" min="0" :max="maxItemDiscountPct" step="0.01"
                       x-model.number="item.discount_percentage" @input="recalculateTotals()"
                       placeholder="Desc. %"
                       class="w-full text-right border-gray-200 rounded-lg text-xs py-1.5 pr-6">
                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400">%</span>
            </div>
        </template>

        <span class="flex-1 text-right text-xs font-bold text-gray-700" x-text="formatMoney(item.price * item.quantity)"></span>
    </div>

    <template x-if="inventoryTrackingEnabled && item.is_stockable && item.quantity > item.stock">
        <p class="text-[10px] text-red-600 font-bold mt-1">Stock insuficiente (disp. <span x-text="item.stock"></span>)</p>
    </template>
</div>
