{{-- Funcionalidades del Sistema (v1.5.1 REQ-3.4): los módulos de config/modules.php
     agrupados por lo que hace el negocio, con qué oculta cada uno y de qué depende.
     Nada se aplica hasta guardar. Datos: SystemFeatures::groups(). --}}
<div class="p-4 md:p-6 flex flex-col gap-6">
    <x-ui.page-header title="Funcionalidades del sistema"
        description="Enciende solo lo que usa tu negocio. Apagar algo no borra nada: deja de registrarse hasta que lo vuelvas a encender." />

    @foreach ($this->groups() as $groupKey => $group)
        @continue(empty($group['modules']))

        <x-ui.infolist.section :title="$group['label']" :icon="$group['icon']" :description="$group['description']" :cols="0"
            wire:key="group-{{ $groupKey }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($group['modules'] as $module)
                    @php
                        $on = (bool) ($selections[$module['key']] ?? false);
                        // Quién lo necesita encendido y sigue encendido: apagarlo se bloquea al guardar.
                        $blockedBy = collect($module['neededBy'])->filter(fn ($label, $dep) => $selections[$dep] ?? false);
                    @endphp

                    <div wire:key="module-{{ $module['key'] }}" @class([
                        'rounded-xl border p-4 flex flex-col gap-3 transition-colors',
                        'border-zertix-primary/40 bg-zertix-primary/5' => $on && ! $module['locked'],
                        'border-gray-100 bg-white' => ! $on || $module['locked'],
                    ])>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 min-w-0">
                                <span @class([
                                    'shrink-0 w-10 h-10 rounded-lg flex items-center justify-center',
                                    'bg-zertix-primary text-white' => $on && ! $module['locked'],
                                    'bg-gray-100 text-gray-400' => ! $on || $module['locked'],
                                ])>
                                    <x-dynamic-component :component="$module['icon']" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0">
                                    @if ($module['byPlan'] && ! $module['locked'])
                                        <x-ui.badge variant="success" size="sm" :dot="false" class="mb-1">Incluido en tu plan</x-ui.badge>
                                    @endif
                                    <p class="text-sm font-semibold text-gray-900">{{ $module['label'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $module['description'] }}</p>
                                </div>
                            </div>

                            @if ($module['locked'])
                                <x-ui.badge variant="slate" size="sm" :dot="false" class="shrink-0">No incluido en tu plan</x-ui.badge>
                            @else
                                {{-- wire:model de ruta anidada + @entangle (docs/ui/forms.md): el
                                     switch conserva su estado de Alpine entre renders. --}}
                                <x-ui.forms.toggle name="module_{{ $module['key'] }}" :checked="$on"
                                    wire:model.live="selections.{{ $module['key'] }}" class="shrink-0" />
                            @endif
                        </div>

                        @if ($module['needs'])
                            <p class="text-xs text-gray-500">
                                <x-heroicon-o-link class="w-3.5 h-3.5 inline -mt-0.5" />
                                Necesita: {{ collect($module['needs'])->join(', ', ' y ') }}.
                            </p>
                        @endif

                        @if (! $on && $blockedBy->isNotEmpty())
                            <p class="text-xs font-medium text-amber-700">
                                {{ $blockedBy->join(', ', ' y ') }} lo necesita: apágalo primero o no podrás guardar.
                            </p>
                        @endif

                        @if ($warnings[$module['key']] ?? null)
                            <p class="text-xs text-amber-700">{{ $warnings[$module['key']] }}</p>
                        @endif

                        @if ($module['hides'])
                            <div class="border-t border-gray-100 pt-3">
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Al apagarlo se oculta</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($module['hides'] as $hidden)
                                        <x-ui.badge variant="slate" size="sm" :dot="false">{{ $hidden }}</x-ui.badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.infolist.section>
    @endforeach

    {{-- Barra fija abajo (diseño Stitch "Funcionalidades del Sistema"). --}}
    <div class="sticky bottom-0 -mx-4 md:-mx-6 -mb-4 md:-mb-6 px-4 md:px-6 py-3 bg-white/95 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
        <p class="text-xs text-gray-400 sm:mr-auto">Nada se aplica hasta que guardes.</p>
        <x-ui.button href="{{ route('configuration.features') }}" appearance="ghost" variant="secondary">Descartar</x-ui.button>
        <x-ui.button type="button" variant="primary" iconLeft="heroicon-s-check" wire:click="save" wire:loading.attr="disabled">
            Guardar cambios
        </x-ui.button>
    </div>
</div>
