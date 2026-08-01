<x-data-table.filter-container formId="terminals-filters">
    <div class="w-full lg:flex-1 text-gray-500 text-sm">
    </div>

    <div class="w-full lg:w-auto flex flex-wrap items-center gap-2">
        <x-data-table.per-page-selector formId="terminals-filters" />
        
        <x-data-table.column-selector 
            :allColumns="$allColumns"
            :visibleColumns="$visibleColumns"
            :defaultDesktop="$defaultDesktop"
            :defaultMobile="$defaultMobile"
            formId="terminals-filters"
        />
    </div>
</x-data-table.filter-container>