<x-data-table.filter-container formId="invoices-filters">
    <div class="w-full lg:flex-1">
        <x-data-table.search 
            formId="invoices-filters" 
            placeholder="Buscar por No. de factura o referencia de venta..." 
        />
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center justify-end gap-2">
        <x-data-table.per-page-selector formId="invoices-filters" />

        <x-data-table.filter-dropdown>

            {{-- GRUPO 1: Filtros Principales --}}
            <x-data-table.filter-group title="Filtros Principales">
                
                <x-data-table.filter-select label="Cliente" name="client_id" formId="invoices-filters">
                    <option value="">Todos los clientes</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-data-table.filter-select>

                <x-data-table.filter-toggle 
                    label="Tipo de Pago" 
                    name="type" 
                    :options="['' => 'Todos', 'cash' => 'Contado', 'credit' => 'Crédito']" 
                    formId="invoices-filters" 
                />

                <x-data-table.filter-toggle 
                    label="Estado Documento" 
                    name="status" 
                    :options="['' => 'Todos', 'active' => 'Vigente', 'cancelled' => 'Anulada']" 
                    formId="invoices-filters" 
                />

                <x-data-table.filter-select label="Formato Original" name="format_type" formId="invoices-filters">
                    <option value="">Todos los formatos</option>
                    @foreach($formats as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-data-table.filter-select>

            </x-data-table.filter-group>

            {{-- GRUPO 2: Rangos --}}
            <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>

                <x-data-table.filter-datetime-range 
                    label="Fecha de Emisión" 
                    formId="invoices-filters" 
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
            formId="invoices-filters" 
        />
    </div>
</x-data-table.filter-container>
