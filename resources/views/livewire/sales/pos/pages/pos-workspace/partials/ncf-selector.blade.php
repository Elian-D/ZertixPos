{{--
    Selector de comprobante fiscal: Sin Comprobante / Consumo (B02) / Crédito Fiscal (B01),
    con captura y verificación de RNC contra la DGII cuando aplica. Compartido entre el
    panel de escritorio y la pestaña "Cobrar" del slide-over móvil — antes pegado dos
    veces casi idéntico (Fase 7.10).

    Props:
    - showLabel (bool): true agrega el heading "Comprobante Fiscal" arriba del selector
      (móvil, donde esta sección vive sola en su propia pestaña sin contexto visual
      alrededor); false lo omite (escritorio, donde el layout de la barra lateral ya
      da suficiente contexto).
    - rncInputBg (string): clase de fondo del input de RNC. Escritorio usa 'bg-gray-50'
      (contraste contra el panel blanco); móvil usa 'bg-white' (el bottom sheet/tab ya
      tiene fondo gris, y un input gris sobre gris se pierde).
--}}
@php
    $showLabel = $showLabel ?? false;
    $rncInputBg = $rncInputBg ?? 'bg-gray-50';
@endphp
<template x-if="usaNcf">
    <div class="space-y-2">
        @if($showLabel)
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Comprobante Fiscal</label>
        @endif

        <div class="grid gap-1.5 p-1 bg-gray-100 rounded-lg"
             :class="creditoFiscalNcfType && formData.client_id != walkinClientId ? 'grid-cols-3' : 'grid-cols-2'">
            <button type="button" @click="selectNcf('none')"
                    :class="ncfChoice === 'none' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:bg-gray-200'"
                    class="py-2 rounded-md text-[11px] font-bold transition-all">
                Sin Comprobante
            </button>
            <template x-if="consumoNcfType">
                <button type="button" @click="selectNcf('consumo')"
                        :class="ncfChoice === 'consumo' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:bg-gray-200'"
                        class="py-2 rounded-md text-[11px] font-bold transition-all">
                    Consumo (B02)
                </button>
            </template>
            <template x-if="creditoFiscalNcfType && formData.client_id != walkinClientId">
                <button type="button" @click="selectNcf('credito')"
                        :class="ncfChoice === 'credito' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:bg-gray-200'"
                        class="py-2 rounded-md text-[11px] font-bold transition-all">
                    Crédito Fiscal (B01)
                </button>
            </template>
        </div>

        {{-- Crédito Fiscal: si el cliente ya tiene RNC en archivo, solo se muestra; si no, se pide y valida --}}
        <template x-if="ncfChoice === 'credito'">
            <div>
                <template x-if="selectedClient?.tax_id">
                    <p class="text-[11px] text-gray-500">
                        RNC/Cédula: <span class="font-bold text-gray-900" x-text="selectedClient.tax_id"></span>
                    </p>
                </template>

                <template x-if="!selectedClient?.tax_id">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">RNC/Cédula del Cliente</label>
                        <div class="flex gap-1.5">
                            <input type="text" x-model="clientRnc" inputmode="numeric"
                                   placeholder="132369076"
                                   class="flex-1 {{ $rncInputBg }} border-gray-200 rounded-lg text-xs text-gray-900 placeholder-gray-400 py-1.5 px-2 focus:ring-[#58c03f] focus:border-[#58c03f]">
                            <button type="button" @click="lookupRnc()" :disabled="rncLookup.loading"
                                    class="px-3 py-1.5 bg-[#58c03f] hover:bg-[#4bad35] disabled:opacity-40 text-white text-[11px] font-bold rounded-lg transition-colors">
                                <span x-text="rncLookup.loading ? '...' : 'Verificar'"></span>
                            </button>
                        </div>
                        <template x-if="rncLookup.error">
                            <p class="text-[11px] text-red-600" x-text="rncLookup.error"></p>
                        </template>
                        <template x-if="rncLookup.data">
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-2">
                                <p class="text-xs font-bold text-emerald-700" x-text="rncLookup.data.nombre_razon_social"></p>
                                <p class="text-[10px] text-emerald-600" x-text="'RNC ' + rncLookup.data.cedula_rnc + ' · ' + rncLookup.data.estado"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</template>
