{{--
    Paso propio (REQ-4.4, separado de Empresa en el rediseño 2026-09-05) — no
    es instalación de módulos a la carta, es curaduría de UX sobre lo que el
    Plan del paso siguiente ya trae (ver docblock de InstallWizard::$businessType).
    Título/subtítulo viven en install-wizard.blade.php (fuera de la card).
    Sin card envolvente única (a diferencia de los demás pasos): acá el grid
    de tarjetas y la barra de navegación son dos bloques separados, igual que
    el mockup Stitch "Instalación Paso 3" — una sola card gigante quedaba
    demasiado alta con 7 tarjetas adentro.
--}}
<div>
    {{-- Omitir (rediseño 2026-09-05, pedido explícito) — elegir tipo de
         negocio es curaduría de UX opcional, nada se rompe sin ella (el
         Plan del paso siguiente sigue siendo el techo real de todos modos,
         ver rulesForStep(2)). nextStep() ya valida businessType como
         `nullable`, así que "Omitir" es literalmente lo mismo que Siguiente
         sin elegir nada — este link solo lo hace explícito para quien no
         quiere pensarlo. --}}
    <div class="flex justify-end mb-3">
        <button type="button" wire:click="nextStep" class="text-xs font-semibold text-gray-400 hover:text-zertix-secondary transition-colors flex items-center gap-1">
            Omitir este paso
            <x-heroicon-s-arrow-right class="w-3 h-3" />
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-4">
        @foreach (\App\Livewire\Install\InstallWizard::businessTypeCards() as $key => $type)
            @php
                $isAvailable = $type['available'] ?? true;
                $isSelected = $isAvailable && $businessType === $key;
                $suggestedModuleLabels = collect($type['suggested_modules'] ?? [])
                    ->map(fn (string $moduleKey) => $this->satelliteModules[$moduleKey]['label'] ?? $moduleKey);
                // Módulos "núcleo flexible" (Inventario, CxC, Cotizaciones) —
                // vienen incluidos en TODA instalación sin importar el tipo
                // de negocio (ver getBaseFlexibleModulesProperty()). Se listan
                // acá para que ninguna tarjeta se vea vacía solo porque ese
                // tipo no sugiere ningún satélite — es información real, no
                // relleno visual.
                $baseFlexibleLabels = collect($this->baseFlexibleModules)->pluck('label');
                $recommendedPlan = $isAvailable ? $this->recommendedPlanFor($key) : null;
            @endphp
            <div
                @if ($isAvailable) wire:click="$set('businessType', '{{ $key }}')" @endif
                @class([
                    'relative rounded-xl border p-4 flex flex-col justify-between transition-all duration-150',
                    'cursor-pointer hover:-translate-y-0.5 hover:shadow-md' => $isAvailable,
                    'border-2 border-zertix-primary shadow-sm bg-white' => $isSelected,
                    'border-gray-200 bg-white hover:border-gray-300' => $isAvailable && ! $isSelected,
                    'border border-dashed border-gray-300 bg-gray-50/75 opacity-85 cursor-not-allowed select-none' => ! $isAvailable,
                ])
            >
                @if ($isSelected)
                    <span class="absolute -top-2.5 right-3 px-2.5 py-0.5 rounded-full bg-zertix-primary text-white text-[10px] font-bold uppercase tracking-wider shadow-sm flex items-center gap-1">
                        <x-heroicon-s-check class="w-3 h-3" /> Seleccionado
                    </span>
                @elseif (! $isAvailable)
                    <span class="absolute -top-2.5 right-3 px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-[10px] font-semibold tracking-wide">
                        Próximamente
                    </span>
                @endif

                <div>
                    <div class="flex items-start gap-3 mb-2.5">
                        <span @class([
                            'w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0',
                            'bg-zertix-secondary text-zertix-primary' => $isSelected,
                            'bg-gray-100 text-gray-600' => $isAvailable && ! $isSelected,
                            'bg-gray-200 text-gray-500' => ! $isAvailable,
                        ])>
                            <x-dynamic-component :component="$type['icon']" class="w-5 h-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 @class(['text-sm font-bold leading-tight', 'text-zertix-secondary' => $isAvailable, 'text-gray-600' => ! $isAvailable])>{{ $type['label'] }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $type['description'] }}</p>
                        </div>
                    </div>

                    <div @class(['mt-3 pt-2.5 border-t', 'border-gray-100' => $isAvailable, 'border-gray-200/60' => ! $isAvailable])>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1.5">
                            {{ $isAvailable ? 'Funcionalidades incluidas:' : 'Funcionalidades planeadas:' }}
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @if ($isAvailable)
                                {{-- Satélite sugerido por este tipo (verde) — lo que se
                                     activa de más respecto a una instalación base. --}}
                                @foreach ($suggestedModuleLabels as $label)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700">{{ $label }}</span>
                                @endforeach
                                {{-- Núcleo flexible (gris) — ya incluido en cualquier
                                     instalación, no es un extra de este tipo puntual. --}}
                                @foreach ($baseFlexibleLabels as $label)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-100 text-gray-600">{{ $label }}</span>
                                @endforeach
                            @else
                                @foreach ($type['planned_features'] ?? [] as $label)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-white border border-gray-200 text-gray-500">{{ $label }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div @class(['mt-4 pt-2.5 border-t flex items-center justify-between text-xs', 'border-gray-100' => $isAvailable, 'border-gray-200/60 text-gray-400' => ! $isAvailable])>
                    @if ($isAvailable)
                        <div class="flex items-center gap-1 min-w-0 text-gray-500">
                            <span class="flex-shrink-0">Sugerido:</span>
                            <span class="font-bold text-zertix-secondary bg-gray-100 px-1 py-0.5 rounded text-[10px] truncate">
                                {{ $recommendedPlan?->name }} (${{ number_format((float) $recommendedPlan?->price, 0) }}/mes)
                            </span>
                        </div>
                        @if ($isSelected)
                            <span class="flex-shrink-0 text-zertix-primary font-semibold text-xs flex items-center gap-0.5">
                                Activo <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                            </span>
                        @else
                            <span class="flex-shrink-0 text-gray-400 font-semibold text-xs">Seleccionar</span>
                        @endif
                    @else
                        <span>En desarrollo</span>
                        <span class="text-[11px] font-medium">No disponible aún</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @error('businessType') <p class="mb-4 text-xs text-red-600 text-center">{{ $message }}</p> @enderror

    {{-- Sin checkbox de ajuste fino acá (quitado 2026-09-05, decisión
         explícita tras revisar UX) — era redundante con los badges verdes de
         cada tarjeta (misma información dos veces) y con "Funcionalidades
         del Sistema" post-instalación, que es la fuente de verdad real para
         esto: un tipo de negocio nunca captura bien un caso con más de una
         operación real (ej. un negocio que además reparte por ruta), así que
         el ajuste fino siempre termina pasando por esa pantalla de todos
         modos. `updatedBusinessType()` sigue precargando `$selectedModules`
         en silencio al elegir tarjeta — sin este bloque, la instalación
         arranca directo con esa sugerencia, sin paso de confirmación
         intermedio. --}}

    {{-- Barra de navegación en su propia card chica, igual que el mockup —
         separada del grid en vez de compartir una card gigante. --}}
    <div class="w-full bg-white rounded-xl border border-gray-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between">
        <x-ui.button type="button" variant="secondary" appearance="ghost" wire:click="prevStep" iconLeft="heroicon-s-arrow-left">
            Atrás: Datos de Empresa
        </x-ui.button>

        <x-ui.button type="button" variant="primary" wire:click="nextStep" iconRight="heroicon-s-arrow-right">
            Siguiente: Ver Plan Recomendado
        </x-ui.button>
    </div>
</div>
