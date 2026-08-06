<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        
        <form action="{{ route('accounting.document_types.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf

            <x-ui.toasts />
            
            <x-form-header
                title="Nuevo Tipo de Documento"
                subtitle="Configure las siglas, correlativos y cuentas automáticas para los documentos del sistema."
                :back-route="route('accounting.document_types.index')" />

            <div class="p-8 space-y-8">
                
                {{-- SECCIÓN 1: IDENTIFICACIÓN --}}
                <section>
                    <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">1</span>
                        Identificación del Documento
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                        <div class="md:col-span-2">
                            <x-input-label value="Nombre del Documento (Ej: Factura de Venta)" />
                            <x-text-input name="name" class="w-full mt-1" :value="old('name')" 
                                placeholder="Escriba el nombre descriptivo..." required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Código / Sigla (Ej: FAC)" />
                            <x-text-input name="code" class="w-full mt-1 font-mono uppercase" :value="old('code')" 
                                placeholder="Sigla única" required />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Prefijo (Opcional)" />
                            <x-text-input name="prefix" class="w-full mt-1 font-mono uppercase" :value="old('prefix')" 
                                placeholder="Ej: F-" />
                            <p class="text-[10px] text-gray-400 mt-1">Si queda vacío, se usará el Código.</p>
                        </div>
                    </div>
                </section>

                {{-- SECCIÓN 2: CORRELATIVO --}}
                <section>
                    <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">2</span>
                        Correlativo
                    </h3>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col items-center justify-center max-w-xs">
                        <x-input-label value="Correlativo Inicial" class="text-gray-400" />
                        <span class="text-3xl font-mono font-bold text-gray-400">0</span>
                        <p class="text-[10px] text-gray-400 mt-2 uppercase text-center tracking-widest">Inicia automáticamente</p>
                        {{-- No enviamos input, el Service/Migración pone el default 0 --}}
                    </div>
                </section>

                {{-- Estado --}}
                <section class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" 
                        {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700">El tipo de documento está activo para uso inmediato.</label>
                </section>
            </div>

            <div class="p-6 bg-gray-100 flex justify-end items-center gap-3 border-t">
                <a href="{{ route('accounting.document_types.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-primary-button class="bg-indigo-600 shadow-lg px-8">
                    Crear Tipo de Documento
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>