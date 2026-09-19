@props([
    'icon' => 'heroicon-o-circle-stack',
    'title' => 'No hay datos para mostrar',
    'description' => 'Parece que aún no hay información registrada en esta sección.',
    'actionLabel' => null,
    'actionClick' => null,
    'variant' => 'dashed' // dashed o simple
])

<div {{ $attributes->merge([
    'class' => 'flex flex-col items-center justify-center py-16 px-6 ' .
               ($variant === 'dashed' ? 'border-2 border-dashed border-slate-200 rounded-2xl' : '')
]) }}>
    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-6">
        <x-dynamic-component :component="$icon" class="w-8 h-8 text-slate-400" />
    </div>

    <h4 class="text-xl font-bold text-zertix-secondary mb-2">{{ $title }}</h4>
    <p class="text-slate-500 text-center max-w-sm text-sm leading-relaxed">
        {{ $description }}
    </p>

    @if($actionLabel)
        <div class="mt-8">
            <x-ui.button
                variant="primary"
                size="md"
                iconLeft="heroicon-s-plus-circle"
                wire:click="{{ $actionClick }}"
            >
                {{ $actionLabel }}
            </x-ui.button>
        </div>
    @endif
</div>
