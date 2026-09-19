<div>

    <x-ui.page-header
        title="Tipos de Comprobante Fiscal"
        description="Catálogo regulado por la DGII — actualizado automáticamente con el sistema."
        :count="$types->total()"
        countLabel="tipos"
    />

    <x-data-table.base-table
        :items="$types"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        @forelse($types as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="full_code" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-slate-700">{{ $item->full_code }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="is_electronic" :visible="$visibleColumns">
                    @if($item->is_electronic)
                        <x-ui.badge variant="success" size="sm" :dot="false">Electrónico</x-ui.badge>
                    @else
                        <x-ui.badge variant="slate" size="sm" :dot="false">Físico</x-ui.badge>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="requires_rnc" :visible="$visibleColumns" class="text-center">
                    @if($item->requires_rnc)
                        <x-heroicon-o-check class="w-4 h-4 text-slate-500 mx-auto" />
                    @else
                        <x-heroicon-o-x-mark class="w-4 h-4 text-slate-300 mx-auto" />
                    @endif
                </x-data-table.cell>

                {{-- REQ-7.3 — el toggle es el único control interactivo del
                     módulo (sin Ver/Editar/Eliminar al lado), así que va
                     directo en su propia columna en vez de dentro de
                     x-ui.action-menu — mismo criterio del mockup de Stitch,
                     no aplica la regla de CLAUDE.md pensada para cuando un
                     toggle convive con otras acciones de fila. --}}
                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.forms.toggle
                        :checked="$item->is_active"
                        wire:click="toggleActivo({{ $item->id }})"
                    />
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <span class="text-slate-300 text-sm">—</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-text" title="No hay tipos de comprobante"
                        description="Intenta ajustar la búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

</div>
