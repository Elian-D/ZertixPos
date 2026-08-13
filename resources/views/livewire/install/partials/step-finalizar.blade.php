{{-- Revisión final, no confirmación de algo ya guardado — nada se persiste
     hasta que se hace click en "Comenzar ahora" (ver InstallWizard::finish()).
     "← Volver" es seguro: no hay nada que deshacer todavía. --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10 text-center">
  <div class="max-w-md mx-auto">
    <div class="w-20 h-20 rounded-full bg-zertix-primary/15 flex items-center justify-center mx-auto mb-6">
        <x-heroicon-s-check-circle class="w-12 h-12 text-zertix-primary" />
    </div>

    <h1 class="text-2xl font-bold text-gray-900">¡Todo listo!</h1>
    <p class="mt-2 text-sm text-gray-500">
        Revisá los datos antes de continuar. Al hacer clic en "Comenzar ahora" se crea tu cuenta y se configura el sistema — hasta entonces, nada se guarda.
    </p>

    <div class="mt-8 bg-gray-50 rounded-xl p-5 text-left space-y-4">
        {{-- ADMINISTRADOR --}}
        <div>
            <h2 class="text-xs font-bold text-zertix-primary uppercase tracking-wide mb-1.5">Administrador</h2>
            <p class="text-sm font-bold text-gray-900">{{ $adminName }}</p>
            <p class="text-xs text-gray-500">{{ $adminEmail }}</p>
        </div>

        <div class="border-t border-gray-200"></div>

        {{-- EMPRESA --}}
        <div>
            <h2 class="text-xs font-bold text-zertix-primary uppercase tracking-wide mb-1.5">Empresa</h2>
            <div class="flex items-center justify-between text-sm py-0.5">
                <span class="text-gray-500">Nombre</span>
                <span class="font-bold text-gray-900 text-right">{{ $nombreEmpresa }}</span>
            </div>
            @if ($taxId)
                <div class="flex items-center justify-between text-sm py-0.5">
                    <span class="text-gray-500">{{ \App\Enums\TaxIdentifierType::tryFrom($taxIdentifierType)?->label() ?? 'Documento' }}</span>
                    <span class="font-bold text-gray-900 text-right">{{ $taxId }}</span>
                </div>
            @endif
            <div class="flex items-center justify-between text-sm py-0.5">
                <span class="text-gray-500">Ubicación</span>
                <span class="font-bold text-gray-900 text-right">
                    {{ $this->selectedMunicipio?->name ? $this->selectedMunicipio->name.', ' : '' }}{{ $this->selectedProvince?->name }}, RD
                </span>
            </div>
        </div>

        <div class="border-t border-gray-200"></div>

        {{-- PLAN SELECCIONADO --}}
        <div>
            <h2 class="text-xs font-bold text-zertix-primary uppercase tracking-wide mb-1.5">Plan Seleccionado</h2>
            <div class="flex items-center justify-between text-sm">
                <span class="font-bold text-gray-900">{{ $this->selectedPlan?->name }}</span>
                {{-- Los planes se cotizan en USD (ver PlanSeeder) — nunca RD$, la moneda
                     regional de Fase 6 es solo para operación diaria del negocio. --}}
                <span class="font-bold text-zertix-primary">
                    @if ($this->selectedPlan?->price !== null)
                        USD${{ number_format($this->selectedPlan->price, 0) }}/mes
                    @else
                        A cotizar
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-3">
        <button type="button" wire:click="prevStep"
            class="border-2 border-zertix-primary text-zertix-primary font-bold py-3.5 rounded-xl transition-colors hover:bg-zertix-primary hover:text-white flex items-center justify-center gap-2 text-sm sm:text-base">
            <x-heroicon-s-arrow-left class="w-4 h-4 flex-shrink-0" />
            Volver
        </button>
        <button type="button" wire:click="finish"
            class="bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm sm:text-base">
            Comenzar ahora
            <x-heroicon-s-arrow-right class="w-4 h-4 flex-shrink-0" />
        </button>
    </div>
  </div>
</div>
