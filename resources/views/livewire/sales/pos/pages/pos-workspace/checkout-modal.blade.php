{{-- MODAL: Selección de método de pago y cobro (compartido entre desktop y móvil).
     Su botón de submit referencia #pos-checkout-form (definido en desktop.blade.php)
     vía el atributo HTML form="pos-checkout-form" — funciona aunque ese <form> esté
     dentro de un contenedor "hidden" en la resolución activa. --}}
<x-modal name="pos-checkout-modal" maxWidth="md">
    <div class="p-6 bg-white">
        <div class="text-center mb-5">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total a Cobrar</p>
            <p class="text-4xl font-black text-gray-900 mt-1" x-text="formatMoney(totals.total)"></p>
        </div>

        <div class="space-y-1 mb-5 text-sm border-y border-gray-100 py-3">
            <div class="flex justify-between text-gray-500">
                <span>Subtotal</span><span x-text="formatMoney(totals.gross)"></span>
            </div>
            <template x-if="totals.discount > 0">
                <div class="flex justify-between text-red-500">
                    <span>Descuento</span><span x-text="'- ' + formatMoney(totals.discount)"></span>
                </div>
            </template>
            <template x-if="usaNcf">
                <div class="flex justify-between text-gray-500">
                    <span>ITBIS</span><span x-text="formatMoney(totals.tax)"></span>
                </div>
            </template>
        </div>

        {{-- Tipo de pago: Crédito no aplica para el Consumidor Final, y requiere
             el módulo sales.receivables (Cuentas por Cobrar) activo — si está
             apagado, el botón se deshabilita en vez de dejar elegirlo y fallar
             luego con "The selected payment type is invalid." (REQ-10.9 bis) --}}
        <div class="grid gap-1.5 p-1 bg-gray-100 rounded-lg mb-3"
             :class="formData.client_id == walkinClientId ? 'grid-cols-1' : 'grid-cols-2'">
            <button type="button" @click="formData.payment_type = 'cash'; onPaymentTypeChange()"
                    :class="formData.payment_type === 'cash' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                    class="py-2 rounded-md text-sm font-bold transition-all">Contado</button>
            <template x-if="formData.client_id != walkinClientId">
                <button type="button" @click="formData.payment_type = 'credit'; onPaymentTypeChange()"
                        :disabled="!receivablesEnabled"
                        :title="!receivablesEnabled ? 'Cuentas por Cobrar está desactivado en Funcionalidades del Sistema' : ''"
                        :class="formData.payment_type === 'credit' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                        class="py-2 rounded-md text-sm font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed">Crédito</button>
            </template>
        </div>

        <template x-if="formData.payment_type === 'cash' && !splitPayment">
            <select x-model.number="formData.tipo_pago_id" @change="onTipoPagoChange()"
                    class="w-full border-gray-200 rounded-lg text-sm text-gray-900 mb-3 focus:ring-[#58c03f] focus:border-[#58c03f]">
                <template x-for="tp in tipoPagos" :key="tp.id">
                    <option :value="tp.id" :selected="tp.id === formData.tipo_pago_id" x-text="tp.nombre" class="text-gray-900"></option>
                </template>
            </select>
        </template>

        {{-- Efectivo recibido: tecleo real + botones de denominación sumables --}}
        <template x-if="formData.payment_type === 'cash' && !splitPayment && isCashMethod">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Efectivo Recibido</label>
                    <input type="text"
                           inputmode="decimal"
                           autocomplete="off"
                           :value="cashReceivedInput"
                           @input="onCashInput($event)"
                           @focus="$event.target.select()"
                           placeholder="0.00"
                           class="w-full text-right text-2xl font-black font-mono text-gray-900 placeholder-gray-400 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-[#58c03f] focus:ring-0">
                </div>

                <div class="grid grid-cols-4 gap-1.5">
                    <template x-for="bill in [2000, 1000, 500, 200, 100, 50]" :key="bill">
                        <button type="button" @click="addCashDenomination(bill)"
                                class="py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition-colors">
                            +<span x-text="bill"></span>
                        </button>
                    </template>
                    <button type="button" @click="clearCashReceived()"
                            class="py-2 rounded-lg bg-red-50 hover:bg-red-100 text-xs font-bold text-red-500 transition-colors">
                        Limpiar
                    </button>
                </div>

                <div class="flex justify-between items-center bg-emerald-50 rounded-xl px-4 py-3">
                    <span class="text-xs font-bold text-emerald-700 uppercase">Cambio</span>
                    <span class="text-xl font-black font-mono text-emerald-700" x-text="formatMoney(formData.cash_change)"></span>
                </div>
            </div>
        </template>

        {{-- Referencia obligatoria para métodos no-efectivo (tarjeta, transferencia,
             depósito, cheque): sin ella no hay forma de rastrear el cobro en el arqueo. --}}
        <template x-if="formData.payment_type === 'cash' && !splitPayment && !isCashMethod">
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                    Referencia <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="paymentReference"
                       placeholder="Últimos 4 dígitos, # de autorización, # de cheque…"
                       class="w-full border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:ring-[#58c03f] focus:border-[#58c03f]">
            </div>
        </template>

        {{-- Toggle de pago dividido: no aplica a crédito (CxC no cobra en el momento). --}}
        <template x-if="formData.payment_type === 'cash' && !splitPayment">
            <button type="button" @click="enableSplitPayment()"
                    class="text-xs font-bold text-gray-400 hover:text-[#58c03f] transition-colors mb-3">
                + Dividir pago
            </button>
        </template>

        {{-- Pago dividido: cada línea es exactamente lo que se aplica (sin "vuelto" por
             línea) — deben sumar el total exacto. Precarga cada línea nueva con lo que
             falta, así el caso típico ("$500 en tarjeta, el resto en efectivo") es
             elegir método + tocar Agregar, sin que el cajero tenga que restar a mano. --}}
        <template x-if="formData.payment_type === 'cash' && splitPayment">
            <div class="space-y-3 mb-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pago Dividido</span>
                    <button type="button" @click="disableSplitPayment()" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors">
                        Un solo método
                    </button>
                </div>

                <template x-for="(payment, pIndex) in payments" :key="pIndex">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 space-y-2">
                        {{-- Select en su propia fila: en pantallas angostas (POS portátiles tipo
                             Sunmi V2), select + monto + quitar en una sola fila se salían del
                             ancho disponible y el input de monto quedaba cortado/inalcanzable. --}}
                        <select x-model.number="payment.tipo_pago_id" @change="payment.reference = ''"
                                class="w-full border-gray-200 rounded-lg text-xs text-gray-900 focus:ring-[#58c03f] focus:border-[#58c03f]">
                            <template x-for="tp in tipoPagos" :key="tp.id">
                                <option :value="tp.id" :selected="tp.id === payment.tipo_pago_id" x-text="tp.nombre" class="text-gray-900"></option>
                            </template>
                        </select>
                        <div class="flex gap-2 items-center">
                            <input type="number" min="0" step="0.01" x-model.number="payment.amount"
                                   class="min-w-0 flex-1 text-right border-gray-200 rounded-lg text-xs text-gray-900 py-1.5 px-2 focus:ring-[#58c03f] focus:border-[#58c03f]">
                            <button type="button" @click="removePaymentLine(pIndex)"
                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors">
                                <x-heroicon-s-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                        <template x-if="!paymentLineIsCash(payment)">
                            <input type="text" x-model="payment.reference"
                                   placeholder="Referencia (últimos dígitos, # autorización…)"
                                   class="w-full border-gray-200 rounded-lg text-xs text-gray-900 placeholder-gray-400 focus:ring-[#58c03f] focus:border-[#58c03f]">
                        </template>
                    </div>
                </template>

                <button type="button" @click="addPaymentLine()" :disabled="paymentsRemaining <= 0"
                        class="w-full py-2 rounded-lg border border-dashed border-gray-300 text-xs font-bold text-gray-500 hover:border-[#58c03f] hover:text-[#58c03f] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    + Agregar método
                </button>

                <div class="flex justify-between items-center rounded-xl px-4 py-3"
                     :class="Math.abs(paymentsRemaining) <= 0.01 ? 'bg-emerald-50' : 'bg-amber-50'">
                    <span class="text-xs font-bold uppercase" :class="Math.abs(paymentsRemaining) <= 0.01 ? 'text-emerald-700' : 'text-amber-700'">Restante</span>
                    <span class="text-xl font-black font-mono" :class="Math.abs(paymentsRemaining) <= 0.01 ? 'text-emerald-700' : 'text-amber-700'" x-text="formatMoney(paymentsRemaining)"></span>
                </div>
            </div>
        </template>

        <template x-if="formData.payment_type === 'credit' && selectedClient">
            <div class="space-y-2">
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Cliente</p>
                        <p class="text-sm font-black text-gray-800" x-text="selectedClient.name"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase font-bold text-gray-400">Crédito Disp.</p>
                        <p :class="exceedsCreditLimit ? 'text-red-600' : 'text-emerald-600'"
                           class="text-sm font-black" x-text="formatMoney(selectedClient.available)"></p>
                    </div>
                </div>

                <template x-if="selectedClient.is_moroso">
                    <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl">
                        <h3 class="text-xs font-bold text-red-800">CLIENTE MOROSO</h3>
                        <p class="text-[11px] text-red-700">Tiene facturas vencidas — la venta a crédito está bloqueada.</p>
                    </div>
                </template>

                <template x-if="!selectedClient.is_moroso && exceedsCreditLimit">
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 rounded-r-xl">
                        <h3 class="text-xs font-bold text-amber-800">LÍMITE DE CRÉDITO EXCEDIDO</h3>
                        <p class="text-[11px] text-amber-700">El total (<span x-text="formatMoney(totals.total)"></span>) supera el disponible del cliente.</p>
                    </div>
                </template>
            </div>
        </template>

        <div class="flex gap-3 mt-6">
            <button type="button"
                    @click="$dispatch('close-modal', 'pos-checkout-modal')"
                    class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition-colors">
                Cancelar
            </button>
            <button type="submit" form="pos-checkout-form" :disabled="isSubmitDisabled || submitting"
                    class="flex-1 px-4 py-3 bg-[#58c03f] hover:bg-[#4bad35] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-sm transition-colors">
                <span x-text="submitting ? 'Procesando…' : 'Confirmar Cobro'"></span>
            </button>
        </div>
    </div>
</x-modal>
