@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         wire:key="pagination-{{ $paginator->getPageName() }}-{{ $paginator->currentPage() }}"
         x-data="{
             goToPage: {{ $paginator->currentPage() }},
             lastPage: {{ $paginator->lastPage() }},
             currentPage: {{ $paginator->currentPage() }},
             jump() {
                 const page = parseInt(this.goToPage);
                 if (page >= 1 && page <= this.lastPage && page !== this.currentPage) {
                     $wire.gotoPage(page, '{{ $paginator->getPageName() }}');
                 } else {
                     this.goToPage = this.currentPage;
                 }
             }
         }"
         class="flex justify-center">

        <div class="flex items-center gap-2
                    bg-white
                    px-2 py-1.5 rounded-2xl
                    ring-1 ring-slate-200/80
                    shadow-sm">

            @if ($paginator->onFirstPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-double-left class="w-4 h-4" />
                </span>
            @else
                <button
                    type="button"
                    wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50">
                    <x-heroicon-s-chevron-double-left class="w-4 h-4" />
                </button>
            @endif

            @if ($paginator->onFirstPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </button>
            @endif

            <div class="h-4 w-px bg-slate-200 mx-1"></div>

            <div class="flex items-center gap-2 px-2">
                <input
                    type="number"
                    x-model.number="goToPage"
                    @keydown.enter="jump()"
                    @blur="jump()"
                    class="w-12 text-center text-sm font-bold rounded-lg py-1
                           bg-slate-50
                           ring-1 ring-slate-200
                           text-zertix-secondary
                           focus:outline-none focus:ring-2 focus:ring-zertix-primary/40
                           transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" />

                <span class="text-xs text-slate-400 font-medium uppercase tracking-tighter">
                    de <span class="text-slate-600">{{ $paginator->lastPage() }}</span>
                </span>
            </div>

            <div class="h-4 w-px bg-slate-200 mx-1"></div>

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </button>
            @else
                <span class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </span>
            @endif

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50">
                    <x-heroicon-s-chevron-double-right class="w-4 h-4" />
                </button>
            @else
                <span class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-double-right class="w-4 h-4" />
                </span>
            @endif

        </div>
    </nav>
@endif
