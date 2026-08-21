<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4">
        <x-ui.toasts />

        {{-- Encabezado plano, sin tarjeta ni botón X — "Cancelar" abajo ya cubre el
             volver atrás, un segundo botón de cierre arriba era redundante. --}}
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-800">Editar: {{ $posTerminal->name }}</h1>
            <p class="text-xs text-gray-500 mt-1">Modifique la configuración técnica de este punto de venta.</p>
        </div>

        <form action="{{ route('sales.pos.terminals.update', $posTerminal) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            @include('sales.pos.terminals.partials.form-fields')

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('sales.pos.terminals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">Actualizar Terminal</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>