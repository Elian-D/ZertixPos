{{-- Modal de feedback: Venta Hecha (compartido entre desktop y móvil) --}}
<x-modal name="pos-success-modal" maxWidth="sm">
    <div class="p-8 bg-white text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-[#58c03f] mx-auto flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-xl font-black text-gray-900">¡Venta Hecha!</h2>
        <template x-if="lastSale">
            <p class="text-sm text-gray-500 mt-1">
                Venta #<span x-text="lastSale.number"></span> — <span x-text="formatMoney(lastSale.total)"></span>
            </p>
        </template>
        <button type="button" @click="$dispatch('close-modal', 'pos-success-modal')"
                class="w-full mt-6 py-3 bg-[#58c03f] hover:bg-[#4bad35] text-white font-bold rounded-xl transition-colors">
            Realizar otra venta
        </button>
    </div>
</x-modal>
