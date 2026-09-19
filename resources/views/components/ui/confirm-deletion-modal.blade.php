{{-- resources/views/components/ui/confirm-deletion-modal.blade.php --}}
@php
    // Si viene $route: <form method="POST"> clásico. Si viene $wireConfirm:
    // el botón de confirmar dispara wire:click en el componente Livewire
    // padre en vez de un submit — mismo modal, dos formas de ejecutar.
    $tag = $wireConfirm ? 'div' : 'form';
@endphp
<x-modal name="confirm-deletion-{{ $id }}" maxWidth="md">
    <{{ $tag }}
        @if($tag === 'form') method="POST" action="{{ $route }}" @endif
        class="p-6"
    >
        @if($tag === 'form')
            @csrf
            @method($method)
        @endif

        <div class="flex items-center gap-4 mb-4 text-red-600">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <x-heroicon-s-exclamation-triangle class="w-7 h-7" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $title }}</h2>
                <p class="text-sm text-gray-500 font-medium italic">Confirmar acción</p>
            </div>
        </div>

        <div class="space-y-3">
            <p class="text-sm text-gray-600 leading-relaxed">
                @if($description)
                    {{-- Si pasas una descripción personalizada, se usa aquí --}}
                    {!! $description !!}
                @else
                    {{-- Comportamiento por defecto --}}
                    {{ $getFormattedType() }}
                    <span class="font-bold text-gray-900 px-1 bg-gray-100 rounded border border-gray-200">
                        {{ $itemName }}
                    </span>
                    será movido a la <span class="text-amber-600 font-semibold italic">papelera de reciclaje</span>.
                @endif
            </p>

            @if($slot->isNotEmpty())
                <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded-r-xl shadow-sm">
                    <div class="flex gap-3">
                        <x-heroicon-s-information-circle class="w-5 h-5 text-amber-600 flex-shrink-0" />
                        <div class="text-[12px] text-amber-800 leading-snug">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-8 flex justify-end items-center gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">
                {{ __('Cancelar') }}
            </x-ui.button>

            @if($wireConfirm)
                <x-ui.button
                    variant="error" iconLeft="heroicon-s-trash" class="px-5 shadow-lg shadow-red-100"
                    wire:click="{{ $wireConfirm }}"
                    x-on:click="$dispatch('close')">
                    {{ __('Confirmar') }}
                </x-ui.button>
            @else
                <x-ui.button type="submit" variant="error" iconLeft="heroicon-s-trash" class="px-5 shadow-lg shadow-red-100">
                    {{ __('Confirmar') }}
                </x-ui.button>
            @endif
        </div>
    </{{ $tag }}>
</x-modal>