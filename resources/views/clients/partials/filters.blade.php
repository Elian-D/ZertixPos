<x-data-table.filter-container formId="clients-filters">
    <div class="w-full lg:flex-1">
        <x-data-table.search 
            formId="clients-filters" 
            placeholder="Buscar por nombre, RNC o email..." 
        />
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-between sm:justify-start lg:justify-end gap-2">

        <x-data-table.bulk-actions :actions="[
            ['id' => 'change_status', 'type' => 'select', 'label' => 'Cambiar Estado', 'icon' => 'heroicon-s-user-group', 'options' => $estadosClientes->map(fn($e) => ['id' => $e->id, 'label' => $e->nombre])],
            ['id' => 'change_geo_state', 'type' => 'select', 'label' => 'Cambiar Región', 'icon' => 'heroicon-s-map-pin', 'options' => $states->map(fn($s) => ['id' => $s->id, 'label' => $s->name])],
            ['id' => 'reset_credit', 'type' => 'none', 'label' => 'Quitar Crédito', 'icon' => 'heroicon-s-no-symbol'],
            ['id' => 'delete', 'type' => 'none', 'label' => 'Eliminar', 'icon' => 'heroicon-s-trash'],
        ]" />

        <div class="flex items-center gap-2">
            <x-data-table.per-page-selector formId="clients-filters" />

            <x-data-table.filter-dropdown>

                {{-- GRUPO 1: Filtros Principales --}}
                <x-data-table.filter-group title="Filtros Principales">

                    <x-data-table.filter-toggle 
                        label="Tipo de Cliente" 
                        name="type" 
                        :options="['' => 'Todos', 'individual' => 'Individuales', 'company' => 'Empresas']" 
                        formId="clients-filters" 
                    />

                    <x-data-table.filter-select label="Estado del Cliente" name="estado_cliente" formId="clients-filters">
                        <option value="">Todos los estados</option>
                        @foreach($estadosClientes as $estado)
                            <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                        @endforeach
                    </x-data-table.filter-select>

                </x-data-table.filter-group>

                {{-- GRUPO 2: Filtros Financieros --}}
                <x-data-table.filter-group title="Estado Financiero" collapsed>

                    <x-data-table.filter-toggle 
                        label="Saldo" 
                        name="has_debt" 
                        :options="['' => 'Todos', 'yes' => 'Con Deuda', 'no' => 'Sin Deuda']" 
                        formId="clients-filters" 
                    />

                    <x-data-table.filter-toggle 
                        label="Estado Crédito" 
                        name="over_limit" 
                        :options="['' => 'Todos', '1' => 'Límite Excedido']" 
                        formId="clients-filters" 
                    />

                </x-data-table.filter-group>

                {{-- GRUPO 3: Rangos --}}
                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>

                    <x-data-table.filter-date-range 
                        label="Fecha de Registro" 
                        formId="clients-filters" 
                    />

                </x-data-table.filter-group>

            </x-data-table.filter-dropdown>
        </div>

        <x-data-table.column-selector 
            :allColumns="$allColumns" 
            :visibleColumns="$visibleColumns" 
            :defaultDesktop="$defaultDesktop"
            :defaultMobile="$defaultMobile"
            formId="clients-filters" 
        />
    </div>
</x-data-table.filter-container>
