<x-data-table.filter-container formId="cash-movements-filters">
    <div class="w-full lg:flex-1">
        <x-data-table.search 
            formId="cash-movements-filters" 
            placeholder="Buscar por razón o referencia..." 
        />
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-end gap-2">
        <x-data-table.per-page-selector formId="cash-movements-filters" />

        <x-data-table.filter-dropdown>

            {{-- GRUPO 1: Filtros Principales --}}
            <x-data-table.filter-group title="Filtros Principales">

                <x-data-table.filter-toggle 
                    label="Tipo de Movimiento" 
                    name="type" 
                    :options="['' => 'Todos', 'in' => 'Entrada', 'out' => 'Salida']" 
                    formId="cash-movements-filters" 
                />

                <x-data-table.filter-select label="Cajero" name="user_id" formId="cash-movements-filters">
                    <option value="">Todos los cajeros</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

            </x-data-table.filter-group>

            {{-- GRUPO 2: Rangos --}}
            <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>

                <x-data-table.filter-datetime-range 
                    label="Fecha del Movimiento" 
                    formId="cash-movements-filters" 
                />

            </x-data-table.filter-group>

        </x-data-table.filter-dropdown>

        <x-data-table.column-selector 
            :allColumns="$allColumns" 
            :visibleColumns="$visibleColumns" 
            :defaultDesktop="$defaultDesktop"
            :defaultMobile="$defaultMobile"
            formId="cash-movements-filters" 
        />
    </div>
</x-data-table.filter-container>
