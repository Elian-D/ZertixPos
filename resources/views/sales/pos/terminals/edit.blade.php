<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4">
        <form action="{{ route('sales.pos.terminals.update', $posTerminal) }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf
            @method('PUT')

            <x-ui.toasts />
            
            <x-form-header
                :title="'Editar: ' . $posTerminal->name"
                subtitle="Modifique la configuración técnica de este punto de venta."
                :back-route="route('sales.pos.terminals.index')" />

            @include('sales.pos.terminals.partials.form-fields')

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <div class="flex-1">
                    <span class="text-[10px] text-gray-400 uppercase font-bold">Configuración de sistema</span>
                </div>
                <a href="{{ route('sales.pos.terminals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 shadow-lg px-8">Actualizar Terminal</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>