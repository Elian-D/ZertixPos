{{-- MODAL CREAR CLIENTE RÁPIDO (POS) --}}
<x-modal name="quick-create-client" maxWidth="md">
    <x-form-header 
        title="Nuevo Cliente Express" 
        subtitle="Complete los datos básicos para facturar de inmediato." />

    {{-- Alpine.js para manejar lógica de tax_id y envío AJAX --}}
    <div x-data="{
            loading: false,
            errorMessage: '',
            name: '',
            tax_id: '',
            tax_identifier_type: '',
            phone: '',
            address: '',

            rncTimer: null,
            rncLookup: { loading: false, error: '', data: null },

            get docTypeLabel() {
                const len = this.tax_id.replace(/\\D/g, '').length;
                if (len === 9) {
                    this.tax_identifier_type = 'RNC';
                    return 'RNC Detectado';
                }
                if (len === 11) {
                    this.tax_identifier_type = 'CEDULA';
                    return 'Cédula Detectada';
                }
                this.tax_identifier_type = '';
                return 'Documento (Opcional)';
            },

            // Debounce: solo consulta la API 900ms después de que el cajero deja de escribir
            // (y solo cuando el largo ya calza con RNC/Cédula), para no pegarle a la API por tecla.
            onTaxIdInput() {
                this.rncLookup = { loading: false, error: '', data: null };
                clearTimeout(this.rncTimer);

                const len = this.tax_id.replace(/\\D/g, '').length;
                if (len === 9 || len === 11) {
                    this.rncTimer = setTimeout(() => this.lookupRnc(), 900);
                }
            },

            async lookupRnc() {
                const rnc = this.tax_id.replace(/\\D/g, '');
                if (rnc.length < 9) return;

                this.rncLookup = { loading: true, error: '', data: null };
                try {
                    const response = await fetch(`{{ route('sales.pos.rnc-lookup') }}?rnc=${rnc}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (!response.ok || data.error) {
                        this.rncLookup = { loading: false, error: data.mensaje || 'No se pudo verificar el RNC.', data: null };
                        return;
                    }

                    this.rncLookup = { loading: false, error: '', data };

                    // Autollenar el nombre solo si el cajero no ha escrito nada todavía,
                    // para no pisar un nombre que ya haya digitado manualmente.
                    if (!this.name) {
                        this.name = data.nombre_comercial || data.nombre_razon_social || '';
                    }
                } catch (e) {
                    this.rncLookup = { loading: false, error: 'Error de conexión al verificar el RNC.', data: null };
                }
            },

            async submitForm() {
                this.errorMessage = '';

                if (!this.name) {
                    this.errorMessage = 'El nombre del cliente es obligatorio.';
                    return;
                }

                this.loading = true;

                try {
                    const response = await fetch('{{ route('sales.pos.quick-customer.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: this.name,
                            tax_id: this.tax_id || null,
                            tax_identifier_type: this.tax_identifier_type || null,
                            phone: this.phone || null,
                            address: this.address || null
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al crear el cliente');
                    }

                    if (data.success) {
                        // Disparar evento para el POS (el workspace lo escucha para seleccionar al cliente
                        // y actualizar el <select> sin recargar la página, ver posWorkspace().init()).
                        window.dispatchEvent(new CustomEvent('pos-client-created', {
                            detail: data.client
                        }));

                        // Toast sin recargar la página (el toast de sesión solo pinta en el HTML inicial).
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                type: 'success',
                                title: 'Cliente creado',
                                message: `${data.client.name} fue registrado y seleccionado.`
                            }
                        }));

                        // Limpiar y Cerrar
                        this.reset();
                        this.$dispatch('close');
                    } else {
                        throw new Error(data.message || 'Error en la validación');
                    }
                } catch (error) {
                    console.error('Error creating client:', error);
                    this.errorMessage = error.message || 'Ocurrió un error al crear el cliente.';
                } finally {
                    this.loading = false;
                }
            },

            reset() {
                this.name = '';
                this.tax_id = '';
                this.tax_identifier_type = '';
                this.phone = '';
                this.address = '';
                this.errorMessage = '';
                this.rncLookup = { loading: false, error: '', data: null };
            }
        }" class="p-6">

        <form @submit.prevent="submitForm()" class="space-y-4">
            {{-- Banner de error --}}
            <div x-show="errorMessage" x-cloak x-transition
                 class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm font-medium">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span x-text="errorMessage"></span>
            </div>

            {{-- 1. Nombre Completo --}}
            <div>
                <x-ui.forms.input
                    id="q-name"
                    label="Nombre del Cliente / Razón Social"
                    x-model="name"
                    placeholder="Ej: Juan Pérez o Empresa S.A.S"
                    required
                    autofocus />
            </div>

            {{-- 2. Documento con Label Inteligente + Autocompletado DGII --}}
            <div>
                <div class="flex justify-between items-center">
                    <x-input-label for="q-tax" x-text="docTypeLabel" />
                    <span class="text-[10px] font-bold text-indigo-600"
                          x-show="tax_identifier_type"
                          x-transition.opacity.duration.300ms
                          x-cloak>
                        AUTO-DETECTADO
                    </span>
                </div>
                <div class="relative">
                    <x-text-input
                        id="q-tax"
                        x-model="tax_id"
                        @input="onTaxIdInput()"
                        class="mt-1 block w-full pr-9"
                        placeholder="00100000000"
                        maxlength="11" />
                    <svg x-show="rncLookup.loading" x-cloak class="animate-spin h-4 w-4 text-indigo-500 absolute right-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <template x-if="rncLookup.error">
                    <p class="mt-1.5 text-xs font-medium text-red-600" x-text="rncLookup.error"></p>
                </template>

                <template x-if="rncLookup.data">
                    <div class="mt-1.5 flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1.5">
                        <x-heroicon-s-check-circle class="w-4 h-4 text-emerald-600 shrink-0" />
                        <p class="text-xs font-bold text-emerald-700 truncate" x-text="rncLookup.data.nombre_comercial || rncLookup.data.nombre_razon_social"></p>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- 3. Teléfono --}}
                <div>
                    <x-ui.forms.input
                        id="q-phone"
                        label="Teléfono"
                        x-model="phone"
                        placeholder="809-000-0000"
                        type="tel" />
                </div>
                
                {{-- 4. Ubicación Helper --}}
                <div class="opacity-60">
                    <x-input-label value="Ubicación Base" />
                    <div class="mt-2 text-xs text-gray-500">
                        <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        {{ general_config()->municipio->name ?? general_config()->provincia->name ?? 'Configurada' }}
                    </div>
                </div>
            </div>

            {{-- 5. Dirección --}}
            <div>
                <x-ui.forms.input
                    id="q-address"
                    label="Dirección Corta"
                    x-model="address"
                    placeholder="Calle, No., Sector..." />
            </div>

            {{-- Botones --}}
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button
                    appearance="ghost"
                    variant="secondary"
                    type="button"
                    @click="$dispatch('close')"
                    x-bind:disabled="loading">
                    Cancelar
                </x-ui.button>
                
                <button
                    type="submit"
                    x-bind:disabled="loading || !name"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <span x-show="!loading">Registrar y Seleccionar</span>
                    
                    <span x-show="loading" class="flex items-center" x-cloak>
                        <svg class="animate-spin h-4 w-4 mr-2 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Procesando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</x-modal>