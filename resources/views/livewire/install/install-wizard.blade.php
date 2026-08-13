<div class="min-h-screen bg-gray-50 py-10 px-4 flex flex-col">
    <div class="w-full mx-auto flex-1 flex flex-col items-center max-w-5xl">

        {{-- LOGO --}}
        <div class="flex items-center gap-2 mb-8">
            <span class="w-10 h-10 rounded-xl bg-zertix-primary flex items-center justify-center shadow-sm">
                <x-heroicon-s-building-storefront class="w-6 h-6 text-white" />
            </span>
            <span class="text-xl font-bold text-gray-900">ZertixPOS</span>
        </div>

        {{-- STEP INDICATOR --}}
        @php
            $steps = [
                ['label' => 'Administrador', 'icon' => 'heroicon-s-user'],
                ['label' => 'Empresa', 'icon' => 'heroicon-s-building-office-2'],
                ['label' => 'Plan', 'icon' => 'heroicon-s-credit-card'],
                ['label' => 'Finalizar', 'icon' => 'heroicon-s-check'],
            ];
        @endphp
        <div class="flex items-center w-full max-w-md mb-10">
            @foreach ($steps as $index => $s)
                <div class="flex flex-col items-center flex-shrink-0">
                    <span @class([
                        'w-9 h-9 rounded-full flex items-center justify-center transition-colors',
                        'bg-zertix-primary text-white' => $index <= $step,
                        'bg-gray-100 text-gray-400' => $index > $step,
                    ])>
                        <x-dynamic-component :component="$s['icon']" class="w-4 h-4" />
                    </span>
                    <span @class([
                        'mt-2 text-[11px] font-semibold whitespace-nowrap',
                        'text-gray-900' => $index <= $step,
                        'text-gray-400' => $index > $step,
                    ])>{{ $s['label'] }}</span>
                </div>

                @if (! $loop->last)
                    <span @class([
                        'flex-1 h-px mx-2 mb-5 transition-colors',
                        'bg-zertix-primary' => $index < $step,
                        'bg-gray-200' => $index >= $step,
                    ])></span>
                @endif
            @endforeach
        </div>

        {{-- CONTENIDO DEL PASO --}}
        <div class="w-full">
            @if ($step === 0)
                @include('livewire.install.partials.step-admin')
            @elseif ($step === 1)
                @include('livewire.install.partials.step-empresa')
            @elseif ($step === 2)
                @include('livewire.install.partials.step-plan')
            @else
                @include('livewire.install.partials.step-finalizar')
            @endif
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="mt-10 flex justify-center">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="w-6 h-6 rounded-full bg-zertix-primary flex items-center justify-center">
                <x-heroicon-s-user class="w-3.5 h-3.5 text-white" />
            </span>
            Soporte ZertixPOS
        </div>
    </div>
</div>
