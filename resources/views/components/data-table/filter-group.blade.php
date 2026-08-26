@props([
    'title',
    'collapsed' => false,
    'activeCount' => 0
])

<div
    x-data="{ expanded: {{ $collapsed ? 'false' : 'true' }} }"
    @class([
        'border-b border-slate-100 rounded-xl last:border-0 -mx-1 transition-all duration-300',
        'bg-slate-50/50' => $activeCount > 0
    ])
>
    <button
        type="button"
        @click="expanded = !expanded"
        class="w-full flex items-center justify-between py-3 px-2 text-left group
               hover:bg-slate-100/50 transition-all duration-200"
    >
        <div class="flex items-center gap-2.5">
            <div @class([
                'flex items-center justify-center w-5 h-5 rounded-lg border transition-all duration-300',
                'bg-zertix-primary/10 border-zertix-primary/20 text-zertix-primary-dark' => $activeCount > 0,
                'bg-slate-100 border-transparent text-slate-400' => $activeCount === 0,
            ])>
                @if($activeCount > 0)
                    <span class="text-[10px] font-black">{{ $activeCount }}</span>
                @else
                    <x-heroicon-s-tag class="w-3 h-3" />
                @endif
            </div>

            <span @class([
                'text-[11px] font-bold uppercase tracking-wider transition-colors duration-200',
                'text-slate-700' => $activeCount > 0,
                'text-slate-500' => $activeCount === 0,
            ])>
                {{ $title }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <x-heroicon-s-chevron-down
                class="w-4 h-4 text-slate-400 transition-transform duration-500"
                ::class="expanded ? 'rotate-180 text-zertix-primary-dark' : ''"
            />
        </div>
    </button>

    <div
        x-show="expanded"
        x-collapse.duration.500ms
        x-cloak
    >
        <div class="space-y-4 pt-1 pb-5 px-3">
            {{ $slot }}
        </div>
    </div>
</div>
