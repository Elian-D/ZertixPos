@props([
    'items'          => null,
    'columns'        => [],     // array ['key' => ['label' => '', 'mobile' => bool]]
    'visibleColumns' => [],
    'activeChips'    => [],
    'hasFilters'     => false,
    'selectable'     => false,  // REQ-0.5 — el hijo lo activa si implementa bulkActions()
    'bulkActions'    => [],
])

@php
    $resolvedColumns = collect($columns)
        ->mapWithKeys(fn ($def, $key) => [$key => $def['label']])
        ->all();

    $desktopDefaults = collect($columns)->keys()->all();
    $mobileDefaults  = collect($columns)
        ->filter(fn ($def) => ($def['mobile'] ?? false) === true)
        ->keys()
        ->all();
@endphp

<div class="flex flex-col w-full gap-2">

    {{-- ══════════════════════════════════════════════════
         TOOLBAR
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-2 flex-wrap">
        <div class="flex-1 min-w-[180px]">
            <x-data-table.search />
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <x-data-table.per-page-selector />
            @if(isset($filterSlot))
                {{ $filterSlot }}
            @endif
            <x-data-table.column-selector
                :columns="$resolvedColumns"
                :visibleColumns="$visibleColumns"
                :desktopDefaults="$desktopDefaults"
                :mobileDefaults="$mobileDefaults" />
        </div>
    </div>

    {{-- CHIPS --}}
    <x-data-table.filter-chips :chips="$activeChips" :hasFilters="$hasFilters" />

    {{-- ══════════════════════════════════════════════════
         TABLA con overlay de carga contextual
    ═══════════════════════════════════════════════════ --}}
    <div class="relative">

        <div
            wire:loading.class="opacity-60 pointer-events-none"
            wire:target="filters,perPage,toggleColumn,resetColumns,gotoPage,nextPage,previousPage,clearFilter,clearAllFilters"
            class="w-full overflow-x-auto rounded-xl border shadow-sm custom-scroll
                   bg-white
                   border-slate-200
                   transition-opacity duration-150">

            <table class="w-full min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        @if($selectable)
                            <th scope="col" class="px-4 py-3.5 w-10">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="w-4 h-4 rounded border-slate-300
                                           text-zertix-primary focus:ring-zertix-primary focus:ring-offset-0
                                           cursor-pointer"
                                />
                            </th>
                        @endif
                        @foreach($resolvedColumns as $key => $label)
                            @if(in_array($key, $visibleColumns))
                                <th scope="col"
                                    class="px-4 py-3.5 text-left text-[11px] font-bold
                                           uppercase tracking-wider
                                           text-slate-500">
                                    {{ $label }}
                                </th>
                            @endif
                        @endforeach
                        <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase
                                   tracking-wider text-slate-500">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        <div
            wire:loading.flex.delay.long
            wire:target="filters,perPage,toggleColumn,resetColumns,gotoPage,nextPage,previousPage,clearFilter,clearAllFilters"
            style="display:none;"
            class="absolute inset-0 rounded-xl z-20
                   flex items-center justify-center
                   cursor-wait
                   bg-white/50
                   backdrop-blur-[2px]">

            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl
                        bg-white
                        border border-slate-200
                        shadow-xl shadow-black/10">

                <svg class="w-4 h-4 text-zertix-primary animate-spin flex-shrink-0"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>

                <span class="text-[11px] font-bold uppercase tracking-widest
                             text-slate-500">
                    Actualizando
                </span>
            </div>
        </div>

    </div>{{-- /relative --}}

    {{-- ══════════════════════════════════════════════════
         FOOTER — contador + paginación
    ═══════════════════════════════════════════════════ --}}
    @if($items && ($items->hasPages() || $items->total() > 0))
        {{ $items->links() }}
    @endif

    {{-- BARRA DE SELECCIÓN MASIVA (REQ-0.5) --}}
    @if($selectable)
        <x-data-table.bulk-actions-bar
            :count="count($selected ?? [])"
            :actions="$bulkActions" />
    @endif

</div>
