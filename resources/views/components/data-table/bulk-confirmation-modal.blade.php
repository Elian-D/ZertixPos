@props(['formId', 'route'])

<x-modal name="confirm-bulk-action" focusable maxWidth="md">
    <div x-data="{ 
        action: '', 
        inputType: '', 
        label: '', 
        ids: [], 
        requiresValue: false,
        options: [],
        selectedValue: '',
        confirmText: '',
        loading: false,
        init() {
            document.addEventListener('execute-bulk-action', (e) => {
                this.action = e.detail.action;
                this.inputType = e.detail.type || 'text';
                this.label = e.detail.label;
                this.ids = e.detail.ids;
                this.requiresValue = e.detail.requiresValue;
                this.options = e.detail.options;
                this.selectedValue = '';
                this.confirmText = '';
                this.$dispatch('open-modal', 'confirm-bulk-action');
            });
        },
        async submit() {
            if (this.confirmText.toLowerCase() !== this.label.toLowerCase()) return;
            
            this.loading = true;
            try {
                const response = await fetch('{{ $route }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: this.action,
                        ids: this.ids,
                        value: this.selectedValue
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Cierra el modal e inmediatamente recarga la página
                    this.$dispatch('close-modal', 'confirm-bulk-action');
                    window.location.reload(); 
                } else {
                    alert(result.message || 'Error al procesar la acción');
                    this.loading = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error en la comunicación con el servidor.');
                this.loading = false;
            }
        }
    }" class="p-6">
        
        <div class="flex items-center justify-center mb-4">
            <div :class="action === 'delete' ? 'bg-red-100' : 'bg-amber-100'" class="p-3 rounded-full transition-colors">
                <template x-if="action === 'delete'">
                    <x-heroicon-s-trash class="w-8 h-8 text-red-600" />
                </template>
                <template x-if="action !== 'delete'">
                    <x-heroicon-s-exclamation-triangle class="w-8 h-8 text-amber-600" />
                </template>
            </div>
        </div>

        <h2 class="text-lg font-bold text-gray-900 text-center">
            Confirmar acción masiva
        </h2>

        <p class="mt-2 text-sm text-gray-500 text-center">
            Vas a aplicar la acción <span class="font-bold text-zertix-primary-600" x-text="label"></span> a 
            <span class="font-bold text-gray-900" x-text="ids.length"></span> registros.
        </p>

        <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                
            <div x-show="requiresValue" class="mb-4">
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2" x-text="'Nuevo valor para ' + label"></label>
                
                <template x-if="inputType === 'select'">
                    <x-ui.forms.select x-model="selectedValue" placeholder="Seleccionar...">
                        <template x-for="opt in options" :key="opt.id">
                            <option :value="opt.id" x-text="opt.label"></option>
                        </template>
                    </x-ui.forms.select>
                </template>

                <template x-if="inputType === 'text'">
                    <x-ui.forms.input type="text" x-model="selectedValue"
                        placeholder="Escriba el nuevo valor..." />
                </template>

                <template x-if="inputType === 'number'">
                    <x-ui.forms.input type="number" x-model="selectedValue" />
                </template>
            </div>

            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">
                Escribe <span class="text-zertix-primary-600 font-black" x-text="label.toLowerCase()"></span> para confirmar:
            </label>
            <x-ui.forms.input type="text"
                x-model="confirmText"
                @keydown.enter="submit"
                x-bind:placeholder="'Escribe ' + label.toLowerCase() + '...'" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" 
                x-on:click="$dispatch('close-modal', 'confirm-bulk-action')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </button>

            <button type="button" 
                @click="submit"
                :disabled="confirmText.toLowerCase() !== label.toLowerCase() || loading"
                class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-zertix-primary-600 rounded-lg hover:bg-zertix-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <template x-if="loading">
                    <x-heroicon-s-arrow-path class="w-4 h-4 mr-2 animate-spin" />
                </template>
                <span x-text="loading ? 'Procesando...' : 'Procesar Cambios'"></span>
            </button>
        </div>
    </div>
</x-modal>