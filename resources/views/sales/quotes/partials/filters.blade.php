<x-data-table.filter-container formId="quotes-filters">
    <div class="w-full lg:flex-1">
        <x-data-table.search 
            formId="quotes-filters" 
            placeholder="Buscar por ID, nombre de cliente o notas..." 
        />
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-end gap-2">
        <x-data-table.per-page-selector formId="quotes-filters" />

        <x-data-table.filter-dropdown>
            
            {{-- GRUPO 1: Filtros Principales --}}
            <x-data-table.filter-group title="Filtros Principales">
                <x-data-table.filter-select label="Cliente" name="customer_id" formId="quotes-filters">
                    <option value="">Todos los clientes</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-select label="Estado" name="status" formId="quotes-filters">
                    <option value="">Todos</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-data-table.filter-select>
                
            </x-data-table.filter-group>

            {{-- GRUPO 2: Origen y Responsable --}}
            <x-data-table.filter-group title="Origen y Usuario" collapsed>
                <x-data-table.filter-toggle label="Origen" name="origin" 
                    :options="array_merge(['' => 'Todos'], $origins)" formId="quotes-filters" />

                <x-data-table.filter-select label="Vendedor / Creador" name="user_id" formId="quotes-filters">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-data-table.filter-select>
            </x-data-table.filter-group>

            {{-- GRUPO 3: Rangos Temporales --}}
            <x-data-table.filter-group title="Rangos de Fecha" collapsed>
                <x-data-table.filter-date-range 
                    label="Fecha de Creación" 
                    formId="quotes-filters" 
                    nameFrom="from_date"
                    nameTo="to_date"
                />
            </x-data-table.filter-group>

        </x-data-table.filter-dropdown>

        <x-data-table.column-selector 
            :allColumns="$allColumns" 
            :visibleColumns="$visibleColumns" 
            :defaultDesktop="$defaultDesktop"
            :defaultMobile="$defaultMobile"
            formId="quotes-filters" 
        />
    </div>
</x-data-table.filter-container>