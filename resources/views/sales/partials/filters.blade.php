<x-data-table.filter-container formId="sales-filters">
    <div class="w-full lg:flex-1">
        <x-data-table.search 
            formId="sales-filters" 
            placeholder="Buscar por No. factura, notas o referencia..." 
        />
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-end gap-2">
        <x-data-table.per-page-selector formId="sales-filters" />

        <x-data-table.filter-dropdown>
            
            {{-- GRUPO 1: Filtros principales (siempre expandidos) --}}
            <x-data-table.filter-group title="Filtros Principales">
                <x-data-table.filter-select label="Cliente" name="client_id" formId="sales-filters">
                    <option value="">Todos los clientes</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-toggle label="Tipo de Pago" name="payment_type" 
                    :options="['' => 'Todos', 'cash' => 'Contado', 'credit' => 'Crédito']" formId="sales-filters" />

                <x-data-table.filter-toggle label="Estado" name="status" 
                    :options="['' => 'Todos', 'completed' => 'Completada', 'canceled' => 'Cancelada']" formId="sales-filters" />
            </x-data-table.filter-group>

            {{-- GRUPO 2: Ubicación (colapsado por defecto) --}}
            <x-data-table.filter-group title="Ubicación" collapsed>
                <x-data-table.filter-select label="Almacén" name="warehouse_id" formId="sales-filters">
                    <option value="">Todos los almacenes</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </x-data-table.filter-select>
            </x-data-table.filter-group>

            {{-- GRUPO 3: POS (colapsado por defecto) --}}
            <x-data-table.filter-group title="Punto de Venta" collapsed>
                <x-data-table.filter-select label="Sesión POS" name="pos_session_id" formId="sales-filters">
                    <option value="">Todas las sesiones</option>
                    @foreach($pos_sessions as $id => $label)
                        <option value="{{ $id }}">Sesión #{{ $label }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-select label="Terminal POS" name="pos_terminal_id" formId="sales-filters">
                    <option value="">Todas las terminales</option>
                    @foreach($pos_terminals as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-data-table.filter-select>
            </x-data-table.filter-group>

            {{-- GRUPO 4: Métodos de pago (colapsado por defecto) --}}
            <x-data-table.filter-group title="Métodos de Pago" collapsed>
                <x-data-table.filter-select label="Método (Efectivo/Transf.)" name="tipo_pago_id" formId="sales-filters">
                    <option value="">Todos los métodos</option>
                    @foreach($tipo_pagos as $pago)
                        <option value="{{ $pago->id }}">{{ $pago->nombre }}</option>
                    @endforeach
                </x-data-table.filter-select>
            </x-data-table.filter-group>

            {{-- GRUPO 5: Rangos (colapsado por defecto) --}}
            <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                <x-data-table.filter-datetime-range 
                    label="Fecha de Venta" 
                    formId="sales-filters" 
                    nameFrom="from_date"
                    nameTo="to_date"
                />

                <x-data-table.filter-range 
                    label="Monto Total" 
                    nameMin="min_amount" 
                    nameMax="max_amount" 
                    formId="sales-filters" 
                />
            </x-data-table.filter-group>

        </x-data-table.filter-dropdown>

        <x-data-table.column-selector 
            :allColumns="$allColumns" 
            :visibleColumns="$visibleColumns" 
            :defaultDesktop="$defaultDesktop"
            :defaultMobile="$defaultMobile"
            formId="sales-filters" 
        />
    </div>
</x-data-table.filter-container>