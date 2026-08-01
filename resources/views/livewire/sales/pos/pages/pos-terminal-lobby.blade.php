<div class="min-h-screen bg-gray-50 flex flex-col p-6 sm:p-10 font-sans selection:bg-[#58c03f] selection:text-white">

    <!-- HEADER DEL LOBBY -->
    <header class="max-w-6xl w-full mx-auto flex justify-between items-center mb-8">
        <div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-400 hover:text-gray-600 transition mb-2">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al panel
            </a>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Terminales de Venta</h1>
            <p class="text-sm text-gray-500">Selecciona una estación de trabajo para iniciar tu turno.</p>
        </div>
        <div class="text-right text-sm">
            <span class="block text-xs text-gray-400 uppercase">Cajero</span>
            <span class="font-semibold text-gray-800">{{ auth()->user()->name }}</span>
        </div>
    </header>

    <!-- MENSAJES DE ERROR GLOBAL -->
    @error('terminal')
        <div class="max-w-6xl w-full mx-auto mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-800 text-sm">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium">{{ $message }}</span>
        </div>
    @enderror

    <!-- GRID DE SELECCIÓN DE TERMINALES -->
    <!-- Quien llega a esta página ya tiene permiso ('permission:pos sessions manage' en la
         ruta) — todas las tarjetas son clickeables por igual. Un turno activo no bloquea,
         solo informa quién lo abrió; cualquier cajero autorizado puede entrar a atenderlo. -->
    <main class="max-w-6xl w-full mx-auto flex-1">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @forelse($terminals as $terminal)
                @php
                    $activeSession = $terminal->sessions->first();
                @endphp

                <div wire:click="selectTerminal({{ $terminal->id }})"
                     class="bg-white border rounded-xl p-4 flex flex-col items-center text-center gap-2 cursor-pointer transition-colors
                            {{ $activeSession ? 'border-amber-200 hover:border-amber-400 hover:shadow-sm' : 'border-sky-100 hover:border-[#58c03f] hover:shadow-sm' }}">

                    <div class="w-11 h-11 rounded-full flex items-center justify-center
                                {{ $activeSession ? 'bg-amber-50 text-amber-500' : 'bg-sky-50 text-sky-500' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 8.25v10.5a1.5 1.5 0 0 0 1.5 1.5h16.5a1.5 1.5 0 0 0 1.5-1.5V8.25M2.25 8.25 4.5 4.5h15l2.25 3.75M9 12.75h6" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-900 leading-tight">{{ $terminal->name }}</h3>
                        <p class="text-xs text-gray-400 truncate">{{ $terminal->warehouse->name ?? 'Sin almacén' }}</p>
                    </div>

                    <span class="text-[11px] font-semibold flex items-center gap-1
                                 {{ $activeSession ? 'text-amber-600' : 'text-sky-600' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $activeSession ? 'bg-amber-500' : 'bg-sky-500' }}"></span>
                        @if($activeSession)
                            Abierto por {{ $activeSession->user->name }}
                        @else
                            Disponible
                        @endif
                    </span>
                </div>
            @empty
                <div class="col-span-full bg-white border border-gray-200 rounded-xl p-12 text-center">
                    <h3 class="text-lg font-bold text-gray-800">No se encontraron terminales</h3>
                    <p class="text-sm text-gray-500 mt-1">Registra y activa terminales desde el panel de configuración.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- MODAL 1: VERIFICACIÓN DE PIN -->
    <x-modal name="pos-pin-modal" maxWidth="md" focusable>
        <form wire:submit.prevent="verifyPin" class="p-6 bg-white">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Seguridad Requerida</h2>
                <p class="text-xs text-gray-500 mt-1">Introduce tu PIN de cajero para continuar.</p>
            </div>

            <div class="mb-5">
                <label for="pin" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">PIN de Seguridad (4 dígitos)</label>
                <input type="password"
                       id="pin"
                       wire:model="pin"
                       maxlength="4"
                       placeholder="••••"
                       class="w-full text-center text-2xl font-bold tracking-widest px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#58c03f] focus:border-[#58c03f] transition-all"
                       autofocus autocomplete="off">
                @error('pin') <span class="block text-xs font-semibold text-red-600 mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button"
                        @click="$dispatch('close-modal', 'pos-pin-modal')"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-[#58c03f] hover:bg-[#4bad35] text-white font-bold text-sm rounded-xl transition-colors">
                    Confirmar Acceso
                </button>
            </div>
        </form>
    </x-modal>

    <!-- MODAL 2: APERTURA DE CAJA -->
    <x-modal name="pos-opening-modal" maxWidth="lg">
        <form wire:submit.prevent="openSession" class="p-6 bg-white">
            <div class="pb-4 mb-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Apertura de Turno</h2>
                <p class="text-xs text-gray-500">Declara el efectivo base con el que inicia la gaveta.</p>
            </div>

            <div class="mb-4">
                <label for="opening_balance" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Fondo de Caja Inicial</label>
                <div class="relative rounded-xl">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-400 font-semibold text-sm">RD$</span>
                    </div>
                    <input type="number"
                           step="0.01"
                           id="opening_balance"
                           wire:model="opening_balance"
                           class="w-full pl-14 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-lg font-bold text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#58c03f] focus:border-[#58c03f] transition-all"
                           placeholder="0.00" autocomplete="off">
                </div>
                @error('opening_balance') <span class="block text-xs font-semibold text-red-600 mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div class="mb-5">
                <label for="notes" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Comentarios (Opcional)</label>
                <textarea id="notes"
                          wire:model="notes"
                          rows="3"
                          placeholder="Denominaciones o condiciones de entrega de la terminal..."
                          class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#58c03f] focus:border-[#58c03f] transition-all resize-none"></textarea>
                @error('notes') <span class="block text-xs font-semibold text-red-600 mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3 pt-2 border-t border-gray-100">
                <button type="button"
                        @click="$dispatch('close-modal', 'pos-opening-modal')"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-[#58c03f] hover:bg-[#4bad35] text-white font-bold text-sm rounded-xl transition-colors">
                    Abrir Estación
                </button>
            </div>
        </form>
    </x-modal>
</div>
