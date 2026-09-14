{{-- Revisión final, no confirmación de algo ya guardado — nada se persiste
     hasta que se hace click en "Comenzar ahora" (ver InstallWizard::finish()).
     "Editar" es seguro: no hay nada que deshacer todavía (goToStep()).
     Título/subtítulo viven en install-wizard.blade.php (fuera de la card) —
     rediseño 2026-09-05, mockup "Instalación Paso 5" (4 secciones con
     ícono+"Editar", no el bloque único con check-circle grande de antes). --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-9">

    {{-- ADMINISTRADOR --}}
    <div class="pb-7">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-zertix-primary flex-shrink-0">
                    <x-heroicon-s-shield-check class="w-5 h-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-zertix-secondary tracking-tight">Administrador del Sistema</h2>
                    <p class="text-xs text-gray-500">Credenciales principales de acceso al panel de control</p>
                </div>
            </div>
            <x-ui.button type="button" variant="secondary" appearance="ghost" size="sm" wire:click="goToStep(0)" iconLeft="heroicon-s-pencil">
                Editar
            </x-ui.button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-100 p-4 rounded-xl">
            <div class="flex flex-col">
                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Nombre Completo</span>
                <span class="text-sm font-semibold text-gray-900 mt-1">{{ $adminName }}</span>
            </div>
            <div class="flex flex-col">
                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Correo de Acceso</span>
                <span class="text-sm font-semibold text-gray-900 mt-1 truncate" title="{{ $adminEmail }}">{{ $adminEmail }}</span>
            </div>
        </div>
    </div>

    <div class="h-px bg-gray-100"></div>

    {{-- EMPRESA --}}
    <div class="py-7">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-zertix-primary flex-shrink-0">
                    <x-heroicon-s-building-office-2 class="w-5 h-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-zertix-secondary tracking-tight">Datos del Negocio</h2>
                    <p class="text-xs text-gray-500">Identidad corporativa y ubicación de operaciones</p>
                </div>
            </div>
            <x-ui.button type="button" variant="secondary" appearance="ghost" size="sm" wire:click="goToStep(1)" iconLeft="heroicon-s-pencil">
                Editar
            </x-ui.button>
        </div>
        <div class="bg-gray-50 border border-gray-100 p-4 sm:p-5 rounded-xl space-y-3.5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold text-gray-900">{{ $nombreEmpresa }}</span>
                    </div>
                    @if ($taxId)
                        <span class="text-xs text-gray-500 font-medium mt-0.5 block">{{ \App\Enums\TaxIdentifierType::tryFrom($taxIdentifierType)?->label() ?? 'Documento' }}: {{ $taxId }}</span>
                    @endif
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-zertix-secondary text-xs font-semibold shadow-sm">
                    <x-heroicon-s-link class="w-3.5 h-3.5 text-zertix-primary" />
                    <span class="font-medium">{{ $subdominio }}.{{ request()->getHost() }}</span>
                    <x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-gray-400" />
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1">
                <div class="flex items-start gap-2 text-gray-500">
                    <x-heroicon-s-map-pin class="w-4 h-4 text-zertix-secondary flex-shrink-0 mt-0.5" />
                    <span class="leading-relaxed">
                        <strong class="text-gray-900 font-semibold">Dirección:</strong> {{ $direccion }}, {{ $this->provinceOptions()[$provinciaId] ?? '' }}, RD
                    </span>
                </div>
                @if ($telefono)
                    <div class="flex items-center gap-2 text-gray-500 sm:justify-end">
                        <x-heroicon-s-phone class="w-4 h-4 text-zertix-secondary flex-shrink-0" />
                        <span class="text-gray-900 font-semibold tracking-wide">{{ $telefono }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="h-px bg-gray-100"></div>

    {{-- TIPO DE NEGOCIO + MÓDULOS ACTIVOS --}}
    <div class="py-7">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-zertix-primary flex-shrink-0">
                    <x-heroicon-s-squares-2x2 class="w-5 h-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-zertix-secondary tracking-tight">Tipo de Operación y Funcionalidades Activas</h2>
                    <p class="text-xs text-gray-500">Características listas para el flujo diario de tu negocio</p>
                </div>
            </div>
            <x-ui.button type="button" variant="secondary" appearance="ghost" size="sm" wire:click="goToStep(2)" iconLeft="heroicon-s-pencil">
                Editar
            </x-ui.button>
        </div>
        @if ($this->businessTypeLabel)
            <x-ui.badge variant="info" size="sm" :dot="false" class="mb-3">{{ $this->businessTypeLabel }}</x-ui.badge>
        @endif
        {{-- Satélite elegido (activado según el tipo de negocio, ver
             InstallWizard::$selectedModules) + núcleo flexible (siempre
             incluido en toda instalación, getBaseFlexibleModulesProperty())
             — ambos en la misma lista: para el dueño, todo esto es
             "lo que mi negocio puede hacer desde el día uno", no le importa
             la distinción interna satélite/flexible. --}}
        <div class="flex flex-wrap gap-2.5">
            @foreach ($selectedModules as $moduleKey)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-100 text-xs text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-zertix-primary"></span>
                    <span class="font-medium">{{ $this->satelliteModules[$moduleKey]['label'] ?? $moduleKey }}</span>
                </div>
            @endforeach
            @foreach ($this->baseFlexibleModules as $module)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-100 text-xs text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-zertix-primary"></span>
                    <span class="font-medium">{{ $module['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="h-px bg-gray-100"></div>

    {{-- PLAN SELECCIONADO + TRIAL --}}
    <div class="pt-7 pb-3">
        <div class="rounded-xl bg-gray-50 border border-gray-100 p-5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1.5 max-w-lg">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-bold text-gray-900">{{ $this->selectedPlan?->name }}</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-white bg-zertix-primary-600 px-2.5 py-0.5 rounded-full">
                        15 días de prueba gratuita, sin tarjeta de crédito
                    </span>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Disfrutá de todas las funcionalidades sin compromiso. El primer cobro automático se hace únicamente después de la prueba de 15 días si decidís continuar.
                </p>
            </div>
            <div class="flex items-baseline md:flex-col md:items-end justify-between md:justify-center flex-shrink-0">
                <div class="flex items-baseline gap-1">
                    @if ($this->selectedPlan?->price !== null)
                        <span class="text-3xl font-extrabold text-gray-900 tracking-tight">USD${{ number_format($this->selectedPlan->price, 0) }}</span>
                        <span class="text-xs font-semibold text-gray-500">/ mes</span>
                    @else
                        <span class="text-2xl font-extrabold text-gray-900">A cotizar</span>
                    @endif
                </div>
                <span class="text-[11px] font-medium text-zertix-primary mt-1">Primer cobro automático después de la prueba</span>
            </div>
        </div>
    </div>

    {{-- ACCIONES --}}
    <div class="pt-6 mt-2 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <x-ui.button type="button" variant="secondary" appearance="ghost" wire:click="prevStep" iconLeft="heroicon-s-arrow-left" class="w-full sm:w-auto">
            Atrás: Cambiar Plan
        </x-ui.button>

        <x-ui.button type="button" variant="primary" wire:click="finish" iconRight="heroicon-s-arrow-right" iconLeft="heroicon-s-rocket-launch" :hoverEffect="true" class="w-full sm:w-auto">
            Comenzar y Crear Mi Negocio
        </x-ui.button>
    </div>

    <p class="mt-4 text-center text-xs text-gray-500 flex items-center justify-center gap-1.5">
        <x-heroicon-s-check-circle class="w-4 h-4 text-zertix-primary" />
        Tu negocio se configurará de inmediato y podrás comenzar a facturar.
    </p>
</div>
