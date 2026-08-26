@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between">

        <div class="hidden sm:block">
            <p class="text-xs text-slate-500">
                Mostrando
                @if ($paginator->firstItem())
                    <span class="font-bold text-zertix-secondary">{{ $paginator->firstItem() }}</span>
                    –
                    <span class="font-bold text-zertix-secondary">{{ $paginator->lastItem() }}</span>
                @endif
                de
                <span class="font-bold text-zertix-secondary">{{ $paginator->total() }}</span>
                resultados
            </p>
        </div>

        <div class="flex items-center gap-1">

            @if ($paginator->onFirstPage())
                <span class="w-10 h-10 flex items-center justify-center rounded-xl
                             text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </span>
            @else
                <button
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                    class="w-10 h-10 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </button>
            @endif

            <div class="flex items-center gap-1 mx-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="w-8 h-10 flex items-center justify-center
                                     text-slate-400 text-sm">
                            &hellip;
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                             rounded-xl text-white
                                             bg-zertix-primary shadow-md shadow-zertix-primary/30">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                           rounded-xl transition-colors
                                           text-slate-500
                                           hover:text-zertix-secondary
                                           hover:bg-slate-100
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
                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                    class="w-10 h-10 flex items-center justify-center rounded-xl transition-colors
                           text-zertix-secondary
                           hover:bg-slate-100
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </button>
            @else
                <span class="w-10 h-10 flex items-center justify-center rounded-xl
                             text-slate-300 cursor-not-allowed">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </span>
            @endif

        </div>
    </nav>
@endif
