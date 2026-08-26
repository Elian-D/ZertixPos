{{-- MODAL: Cobro de CxC desde el Workspace (Fase 6, REQ-6.2/6.3/6.4). Compartido entre
     desktop y móvil, igual que checkout-modal — un solo <form> real que postea a
     collectUrl, con 3 pasos condicionados por x-show: cliente -> factura -> monto/pago.

     Colores (ajuste post-feedback, REQ-6.3 extra):
     - Gris/negro fuerte para "cuánto debe" (cliente, lista) — es un dato informativo,
       no una advertencia por sí solo; usar ámbar ahí competía visualmente con la
       verdadera señal de urgencia (factura vencida).
     - Rojo, exclusivo para "factura vencida" — la única señal que de verdad necesita
       parar al cajero en seco.
     - Verde de marca (#58c03f) para el saldo de la factura ya elegida — mismo color
       que el resto del flujo de cobro/confirmación, lectura positiva ("esto es lo
       que vas a cobrar ahora"). --}}
<x-modal name="pos-collect-modal" maxWidth="md">
    <form method="POST" :action="collectUrl" @submit="onCollectSubmit" class="p-5 bg-white flex flex-col min-h-0" style="max-height: 85vh;">
        @csrf
        <input type="hidden" name="receivable_id" :value="collectReceivableId">
        <input type="hidden" name="tipo_pago_id" :value="collectTipoPagoId">
        <input type="hidden" name="amount" :value="collectAmount">
        <input type="hidden" name="reference" :value="collectReference">

        <div class="flex items-center justify-between mb-3 shrink-0">
            <h2 class="font-bold text-gray-900">Cobrar Cuenta por Cobrar</h2>
            <button type="button" @click="$dispatch('close-modal', 'pos-collect-modal')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <x-heroicon-s-x-mark class="w-4 h-4 text-gray-500" />
            </button>
        </div>

        {{-- Paso 1: Cliente con deuda --}}
        <template x-if="!collectClientId">
            <div class="overflow-y-auto min-h-0 -mx-1 px-1">
                <input type="text" x-model="collectClientSearch" placeholder="Buscar cliente..."
                       class="w-full mb-3 border-gray-200 rounded-lg text-sm shrink-0">

                <template x-if="filteredDebtors.length === 0">
                    <p class="text-xs text-gray-400 text-center py-6">Ningún cliente tiene cuentas por cobrar pendientes.</p>
                </template>

                <div class="space-y-1">
                    <template x-for="debtor in filteredDebtors" :key="debtor.id">
                        <button type="button" @click="selectCollectClient(debtor.id)"
                                class="w-full flex items-center justify-between px-3 py-3 rounded-lg hover:bg-gray-50 text-left transition-colors">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="text-sm font-bold text-gray-800 truncate" x-text="debtor.name"></span>
                                <span class="shrink-0 text-[10px] font-bold text-gray-500 bg-gray-100 rounded-full px-2 py-0.5"
                                      x-text="debtor.receivables.length + (debtor.receivables.length === 1 ? ' factura' : ' facturas')"></span>
                            </span>
                            <span class="shrink-0 text-base font-mono font-black text-gray-900" x-text="formatMoney(debtor.balance)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        {{-- Paso 2: Factura (Receivable) del cliente elegido — orden FIFO real (la más
             vieja primero, ver PosWorkspace::getDebtors()); solo la primera de la lista
             es seleccionable, el resto queda bloqueada con candado — no se puede cobrar
             una factura nueva salteando una más vieja del mismo cliente. --}}
        <template x-if="collectClientId && !collectReceivableId">
            <div class="overflow-y-auto min-h-0 -mx-1 px-1">
                <button type="button" @click="selectCollectClient('')"
                        class="flex items-center gap-1.5 text-sm font-bold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg px-3 py-2 mb-3 transition-colors">
                    <x-heroicon-s-chevron-left class="w-4 h-4" /> Cambiar Cliente
                </button>
                <p class="text-sm font-bold text-gray-800 mb-2" x-text="selectedDebtor?.name"></p>

                <div class="space-y-1.5">
                    <template x-for="(receivable, index) in (selectedDebtor?.receivables ?? [])" :key="receivable.id">
                        <button type="button" @click="index === 0 && selectCollectReceivable(receivable.id)"
                                :disabled="index > 0"
                                :title="index > 0 ? 'Primero debe cobrarse la factura más antigua de este cliente' : ''"
                                class="w-full flex items-center justify-between px-3 py-3 rounded-lg border text-left transition-colors"
                                :class="index === 0 ? 'border-gray-200 hover:border-[#58c03f] hover:bg-emerald-50 cursor-pointer' : 'border-gray-100 bg-gray-50 cursor-not-allowed opacity-70'">
                            <span class="min-w-0">
                                <span class="flex items-center gap-1.5">
                                    <span class="text-sm font-bold text-gray-800" x-text="receivable.document_number"></span>
                                    <x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-gray-400" x-show="index > 0" />
                                </span>
                                <span class="block text-xs font-semibold text-gray-500 mt-0.5" x-text="'Vence: ' + (receivable.due_date ?? 'N/A')"></span>
                                <span x-show="receivable.is_overdue"
                                      class="inline-block mt-1 text-[10px] font-black uppercase tracking-wide text-white bg-red-600 rounded px-1.5 py-0.5"
                                      x-text="'VENCIDA — ' + receivable.days_overdue + (receivable.days_overdue === 1 ? ' DÍA' : ' DÍAS')"></span>
                            </span>
                            <span class="shrink-0 text-base font-mono font-black text-gray-900" x-text="formatMoney(receivable.current_balance)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        {{-- Paso 3: Monto del abono y método de pago --}}
        <template x-if="collectReceivableId">
            <div class="space-y-3 overflow-y-auto min-h-0 -mx-1 px-1">
                <button type="button" @click="selectCollectReceivable('')"
                        class="flex items-center gap-1.5 text-sm font-bold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg px-3 py-2 transition-colors">
                    <x-heroicon-s-chevron-left class="w-4 h-4" /> Cambiar Factura
                </button>

                <div class="bg-emerald-50 rounded-lg p-3.5 flex justify-between items-center border border-emerald-100">
                    <span class="text-xs font-bold text-gray-600" x-text="selectedReceivable?.document_number"></span>
                    <span class="text-xl font-mono font-black text-[#58c03f]" x-text="formatMoney(selectedReceivable?.current_balance)"></span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Monto a Abonar</label>
                    <input type="number" x-ref="collectAmountInput" x-model.number="collectAmount"
                           :disabled="!collectAmountUnlocked" min="0.01" step="0.01"
                           :max="selectedReceivable?.current_balance"
                           class="w-full text-right text-2xl font-black font-mono text-gray-900 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-[#58c03f] focus:ring-0 disabled:bg-gray-50 disabled:text-gray-400">
                </div>

                <div class="flex flex-col sm:flex-row gap-2.5 sm:gap-2">
                    <button type="button" @click="setCollectHalf()"
                            class="flex-1 py-3.5 sm:py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm sm:text-xs font-bold text-gray-700 transition-colors">
                        Abonar la Mitad
                    </button>
                    <button type="button" @click="setCollectFull()"
                            class="flex-1 py-3.5 sm:py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm sm:text-xs font-bold text-gray-700 transition-colors">
                        Saldar Total
                    </button>
                    <button type="button" @click="unlockCollectAmount()"
                            class="flex-1 py-3.5 sm:py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm sm:text-xs font-bold text-gray-700 transition-colors">
                        Otro Monto
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Método de Pago</label>
                    <select x-model.number="collectTipoPagoId" class="w-full border-gray-200 rounded-lg text-sm text-gray-900 focus:ring-[#58c03f] focus:border-[#58c03f]">
                        <template x-for="tp in tipoPagos" :key="tp.id">
                            <option :value="tp.id" :selected="tp.id === collectTipoPagoId" x-text="tp.nombre"></option>
                        </template>
                    </select>
                </div>

                {{-- Referencia opcional para métodos no-efectivo/no-tarjeta, nunca bloquea
                     el envío — mismo criterio relajado del checkout de venta (Fase 6,
                     REQ-6.9). --}}
                <template x-if="!collectIsCashMethod && !collectIsCardMethod">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                            Referencia (opcional)
                        </label>
                        <input type="text" x-model="collectReference"
                               placeholder="Últimos 4 dígitos, # de autorización, # de cheque…"
                               class="w-full border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:ring-[#58c03f] focus:border-[#58c03f]">
                    </div>
                </template>

                <button type="submit" :disabled="collectSubmitDisabled || collectSubmitting"
                        class="w-full px-4 py-3 bg-[#58c03f] hover:bg-[#4bad35] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-sm transition-colors">
                    <span x-text="collectSubmitting ? 'Procesando…' : 'Confirmar Cobro'"></span>
                </button>
            </div>
        </template>
    </form>
</x-modal>
