@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         x-data="{
             goToPage: {{ $paginator->currentPage() }},
             jump() {
                 const page = parseInt(this.goToPage);
                 const total = {{ $paginator->lastPage() }};
                 if (page >= 1 && page <= total) {
                     $wire.gotoPage(page, '{{ $paginator->getPageName() }}');
                 }
             }
         }"
         class="flex flex-wrap items-center justify-between gap-4
                bg-slate-50 px-5 py-3.5 rounded-xl">

        <div class="flex items-center gap-2">

            @if ($paginator->onFirstPage())
                <span class="flex items-center gap-1 px-3 py-2 rounded-xl
                             text-slate-300 cursor-not-allowed text-sm font-bold uppercase tracking-wide">
                    <x-heroicon-s-chevron-left class="w-4 h-4" />
                    Anterior
                </span>
            @else
                <button
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-1 px-3 py-2 rounded-xl transition-colors text-sm font-bold uppercase tracking-wide
                           text-zertix-secondary
                           hover:bg-slate-200
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-left class="w-4 h-4" />
                    Anterior
                </button>
            @endif

            <div class="flex items-center gap-1 mx-2">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="w-10 h-10 flex items-center justify-center text-sm
                                     text-slate-400">
                            &hellip;
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                             rounded-xl text-white
                                             bg-zertix-primary shadow-lg shadow-zertix-primary/25">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                           rounded-xl transition-all
                                           text-slate-500
                                           hover:text-zertix-secondary
                                           hover:bg-slate-200
                                           disabled:opacity-50 disabled:cursor-wait">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <button
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-1 px-3 py-2 rounded-xl transition-colors text-sm font-bold uppercase tracking-wide
                           text-zertix-secondary
                           hover:bg-slate-200
                           disabled:opacity-50 disabled:cursor-wait">
                    Siguiente
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                </button>
            @else
                <span class="flex items-center gap-1 px-3 py-2 rounded-xl
                             text-slate-300 cursor-not-allowed text-sm font-bold uppercase tracking-wide">
                    Siguiente
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                </span>
            @endif

        </div>

        <div class="flex items-center gap-6">

            <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

            <div class="flex items-center gap-3">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Ir a página
                </span>
                <div class="flex items-center gap-2">
                    <input
                        type="number"
                        x-model="goToPage"
                        min="1"
                        max="{{ $paginator->lastPage() }}"
                        @keydown.enter="jump()"
                        class="w-14 text-center text-sm font-bold rounded-xl py-2
                               bg-white
                               ring-1 ring-slate-200
                               text-zertix-secondary
                               focus:outline-none focus:ring-2 focus:ring-zertix-primary/40
                               transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" />
                    <button
                        @click="jump()"
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest
                               bg-zertix-secondary text-white
                               hover:opacity-90 active:scale-[0.98] transition-all">
                        IR
                    </button>
                </div>
            </div>

            <span class="text-xs text-slate-400 hidden lg:block">
                Página
                <span class="font-bold text-slate-600">{{ $paginator->currentPage() }}</span>
                de
                <span class="font-bold text-slate-600">{{ $paginator->lastPage() }}</span>
            </span>

        </div>
    </nav>
@endif
