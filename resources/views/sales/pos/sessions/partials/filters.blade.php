<x-data-table.filter-container formId="pos-sessions-filters">
    <div class="w-full lg:flex-1">
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-end gap-2">
        <x-data-table.per-page-selector formId="pos-sessions-filters" />

        <x-data-table.filter-dropdown>

            {{-- GRUPO 1: Filtros Principales --}}
            <x-data-table.filter-group title="Filtros Principales">

                <x-data-table.filter-select label="Terminal" name="terminal_id" formId="pos-sessions-filters">
                    <option value="">Todas las terminales</option>
                    @foreach($terminals as $terminal)
                        <option value="{{ $terminal->id }}">{{ $terminal->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-select label="Abierto Por" name="opened_by_user_id" formId="pos-sessions-filters">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-select label="Cerrado Por" name="closed_by_user_id" formId="pos-sessions-filters">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-toggle
                    label="Estado"
                    name="status"
                    :options="['' => 'Todos', 'open' => 'Abierta', 'closed' => 'Cerrada']"
                    formId="pos-sessions-filters"
                />

                <x-data-table.filter-select label="Motivo de Descuadre" name="difference_reason" formId="pos-sessions-filters">
                    <option value="">Todos los motivos</option>
                    @foreach($difference_reasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-data-table.filter-select>

            </x-data-table.filter-group>

            {{-- GRUPO 2: Rangos --}}
            <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>

                <x-data-table.filter-datetime-range 
                    label="Fecha de Apertura" 
                    formId="pos-sessions-filters" 
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
            formId="pos-sessions-filters" 
        />
    </div>
</x-data-table.filter-container>
