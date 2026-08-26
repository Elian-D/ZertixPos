@props([
    'title', 
    'uploadRoute', 
    'templateRoute'
])

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
        {{-- Header --}}
        <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Importación de {{ $title }}</h1>
                <p class="text-gray-500 mt-1 text-sm">Siga las instrucciones para cargar sus datos masivamente.</p>
            </div>
                <a href="{{ route('clients.index') }}" class="p-2 bg-white border rounded-lg text-gray-400 hover:text-zertix-primary-600 transition shadow-sm">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </a>
        </div>

        <div class="p-8 grid md:grid-cols-2 gap-12">
            {{-- Columna 1: Preparación --}}
            <div class="space-y-6">
                <div>
                    <h3 class="font-bold text-gray-700 mb-3 flex items-center text-base">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-zertix-primary-100 text-zertix-primary-600 text-xs mr-3">1</span>
                        Preparación del archivo
                    </h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Utilice la plantilla oficial. Los nombres de las columnas deben coincidir exactamente para evitar errores.
                    </p>
                </div>
                
                <x-data-table.import.template-card :route="$templateRoute" />

                <div class="pt-4 border-t border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Recursos de Referencia</span>
                    <div class="grid grid-cols-1 gap-3 mt-4">
                        {{ $catalogs }} {{-- Slot para los catalog-link --}}
                    </div>
                </div>
            </div>

            {{-- Columna 2: Upload --}}
            {{-- Se deja el <input type="file"> nativo con su caja "drag & drop" bespoke
                 (borde punteado, ícono grande, texto "PROCESAR DATOS" en botón separado):
                 no encaja visualmente en x-ui.forms.file-input, que está diseñado como
                 campo de formulario estándar (caja delgada estilo input), no como zona de
                 importación masiva. Migrarlo aquí cambiaría el diseño de esta pantalla. --}}
            <div x-data="{ fileName: '', uploading: false }" class="bg-zertix-primary-50/40 rounded-3xl p-8 border-2 border-dashed border-zertix-primary-200 flex flex-col justify-center">
                <h3 class="font-bold text-gray-700 mb-6 flex items-center text-base">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-zertix-primary-600 text-white text-xs mr-3">2</span>
                    Cargar archivo
                </h3>

                <form action="{{ route($uploadRoute) }}" method="POST" enctype="multipart/form-data" @submit="uploading = true">
                    @csrf
                    <label class="relative group cursor-pointer"
                            :class="uploading ? 'pointer-events-none opacity-60' : ''">
                        <div class="flex flex-col items-center justify-center py-6 bg-white rounded-2xl border border-zertix-primary-100 group-hover:border-zertix-primary-400 transition-colors shadow-sm">
                            <x-heroicon-s-cloud-arrow-up class="w-10 h-10 text-zertix-primary-400 group-hover:text-zertix-primary-600 mb-2 transition-transform group-hover:-translate-y-1" />
                            <span class="text-xs font-medium text-gray-500" x-text="fileName ? fileName : 'Seleccionar archivo .xlsx'"></span>
                        </div>
                        
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="hidden" @change="fileName = $el.files[0].name"/>
                    </label>
                    
                    <button type="submit" 
                            :disabled="uploading"
                            class="w-full mt-8 bg-zertix-primary-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-zertix-primary-200 hover:bg-zertix-primary-700 transition-all active:scale-[0.98] disabled:opacity-50">
                        <span x-show="!uploading">PROCESAR DATOS</span>
                        <span x-show="uploading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24"></svg>
                            PROCESANDO...
                        </span>
                    </button>
                </form>

                @if($errors->any())
                    <div class="mt-6 p-4 bg-red-50 rounded-2xl border border-red-100 overflow-hidden">
                        <div class="flex items-center mb-2 text-red-700">
                            <x-heroicon-s-x-circle class="w-5 h-5 mr-2" />
                            <span class="text-xs font-bold uppercase italic">Se encontraron errores:</span>
                        </div>
                        <ul class="text-[11px] text-red-600 list-disc pl-5 space-y-1 max-h-40 overflow-y-auto">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>