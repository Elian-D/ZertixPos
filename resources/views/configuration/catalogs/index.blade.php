<x-app-layout>

    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-xl rounded-lg p-6">

            {{-- TÍTULO --}}
            <h2 class="text-xl font-semibold text-gray-800 mb-6 border-b pb-3">
                Catálogos del Sistema
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('configuration.pagos.index') }}"
                   class="block bg-white border border-gray-200 rounded-2xl p-6 hover:border-zertix-primary hover:shadow-md transition">
                    <x-heroicon-s-credit-card class="w-8 h-8 text-zertix-primary mb-3" />
                    <h3 class="font-bold text-gray-800">Métodos de Pago</h3>
                    <p class="text-xs text-gray-400 mt-1">Efectivo, tarjeta, transferencia y personalizados.</p>
                </a>

                <a href="{{ route('configuration.document_types.index') }}"
                   class="block bg-white border border-gray-200 rounded-2xl p-6 hover:border-zertix-primary hover:shadow-md transition">
                    <x-heroicon-s-document-text class="w-8 h-8 text-zertix-primary mb-3" />
                    <h3 class="font-bold text-gray-800">Tipos de Documento</h3>
                    <p class="text-xs text-gray-400 mt-1">Correlativos de Facturas, Cobros y futuros documentos.</p>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
