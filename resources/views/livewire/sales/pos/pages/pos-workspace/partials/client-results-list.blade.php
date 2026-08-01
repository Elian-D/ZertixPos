{{--
    Resultados de la búsqueda de clientes (filteredClients) + estado vacío. Compartido
    entre el modal de escritorio y el bottom sheet móvil. El wrapper con scroll/padding
    queda en cada caller (difiere: modal vs bottom sheet).

    Props:
    - closeAction (string): expresión Alpine que cierra el picker tras seleccionar un
      cliente. Modal: "$dispatch('close-modal', 'pos-client-modal')".
      Bottom sheet: "clientSheetOpen = false".
--}}
@php $closeAction = $closeAction ?? ''; @endphp
<template x-for="client in filteredClients" :key="'cl-'+client.id">
    <button type="button" @click="formData.client_id = client.id; onClientChange(); {{ $closeAction }}"
            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left"
            :class="formData.client_id === client.id ? 'bg-emerald-50' : 'hover:bg-gray-50'">
        <div class="min-w-0">
            <p class="text-sm font-bold truncate" :class="formData.client_id === client.id ? 'text-[#58c03f]' : 'text-gray-800'" x-text="client.name"></p>
            <p class="text-[11px] text-gray-400" x-text="client.tax_id || 'Sin RNC/Cédula'"></p>
        </div>
        <x-heroicon-s-check-circle class="w-5 h-5 text-[#58c03f] shrink-0" x-show="formData.client_id === client.id" />
    </button>
</template>
<template x-if="filteredClients.length === 0">
    <p class="text-center text-gray-400 text-sm italic py-8">Sin resultados para "<span x-text="clientSearch"></span>".</p>
</template>
