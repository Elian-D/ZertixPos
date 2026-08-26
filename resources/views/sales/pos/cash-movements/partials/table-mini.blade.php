<div class="overflow-hidden border border-gray-200 rounded-xl">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hora</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Motivo</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Monto</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($items as $movement)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                        {{ $movement->created_at->format('H:i:s') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($movement->type === 'in')
                            <x-ui.badge variant="success" size="sm" icon="heroicon-s-arrow-small-up">Entrada</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning" size="sm" icon="heroicon-s-arrow-small-down">Salida</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ Str::limit($movement->reason, 40) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold {{ $movement->type === 'in' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $movement->type === 'in' ? '+' : '-' }} {{ config('regional.currency_symbol') }}{{ number_format($movement->amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                        No hay movimientos registrados en esta sesión.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>