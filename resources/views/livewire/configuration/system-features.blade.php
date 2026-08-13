<div class="max-w-5xl mx-auto py-8 px-4 space-y-8">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Funcionalidades del Sistema</h1>
        <p class="mt-1 text-sm text-gray-500">
            Activá o desactivá módulos según cómo opera tu negocio. Nada se aplica hasta que guardes los cambios.
        </p>
    </div>

    {{-- Módulos de tu Plan (satélite) — apagados por defecto, gateados por Plan --}}
    <section>
        <div class="mb-4">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <x-heroicon-s-squares-2x2 class="w-5 h-5 text-zertix-primary" />
                Módulos de tu Plan
            </h2>
            <p class="text-xs text-gray-400 mt-1">Funcionalidad adicional incluida en tu Plan actual.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($this->satelliteModules() as $module)
                @php $on = $selections[$module['key']] ?? false; @endphp
                <div class="bg-white rounded-2xl border p-5 transition-colors {{ $on ? 'border-zertix-primary/40 ring-1 ring-zertix-primary/20' : 'border-gray-100' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="shrink-0 w-10 h-10 rounded-xl bg-zertix-primary/10 text-zertix-primary flex items-center justify-center">
                                <x-dynamic-component :component="$module['icon']" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800">{{ $module['label'] }}</p>
                                @if ($module['description'])
                                    <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $module['description'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if (! $module['includedInPlan'])
                            <a href="#" class="text-[11px] font-bold text-amber-600 uppercase tracking-wide shrink-0 mt-1">Mejorar Plan</a>
                        @else
                            <button type="button" wire:click="toggle('{{ $module['key'] }}')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0 {{ $on ? 'bg-zertix-primary' : 'bg-gray-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $on ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        @endif
                    </div>

                    @if ($warnings[$module['key']] ?? null)
                        <p class="mt-3 text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">{{ $warnings[$module['key']] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Preferencias de Operación (núcleo flexible) — encendidos por defecto en todo Plan --}}
    <section>
        <div class="mb-4">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <x-heroicon-s-adjustments-horizontal class="w-5 h-5 text-zertix-primary" />
                Preferencias de Operación
            </h2>
            <p class="text-xs text-gray-400 mt-1">
                Vienen encendidas siempre, sin importar tu Plan — apagalas si genuinamente no aplican a tu negocio.
                Apagar una no borra nada: solo deja de registrar información nueva de ese tipo.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($this->flexibleModules() as $module)
                @php $on = $selections[$module['key']] ?? true; @endphp
                <div class="bg-white rounded-2xl border p-5 transition-colors {{ $on ? 'border-zertix-primary/40 ring-1 ring-zertix-primary/20' : 'border-gray-100' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="shrink-0 w-10 h-10 rounded-xl bg-zertix-primary/10 text-zertix-primary flex items-center justify-center">
                                <x-dynamic-component :component="$module['icon']" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800">{{ $module['label'] }}</p>
                                @if ($module['description'])
                                    <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $module['description'] }}</p>
                                @endif
                            </div>
                        </div>

                        <button type="button" wire:click="toggle('{{ $module['key'] }}')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0 {{ $on ? 'bg-zertix-primary' : 'bg-gray-200' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $on ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>

                    @if ($warnings[$module['key']] ?? null)
                        <p class="mt-3 text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">{{ $warnings[$module['key']] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex justify-end">
        <button wire:click="save"
            class="bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold px-6 py-3 rounded-xl transition-colors">
            Guardar cambios
        </button>
    </div>
</div>
