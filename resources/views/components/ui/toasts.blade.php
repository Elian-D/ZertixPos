{{-- toast.blade.php --}}
<div class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 w-full max-w-sm px-4 md:px-0"
     x-data="{
        dynamic: [],
        styles: {
            success: { border: 'border-emerald-500', bg: 'bg-emerald-50', icon: 'text-emerald-600' },
            error:   { border: 'border-red-500',     bg: 'bg-red-50',     icon: 'text-red-600' },
            info:    { border: 'border-blue-500',     bg: 'bg-blue-50',    icon: 'text-blue-600' },
        },
        styleFor(type) { return this.styles[type] || this.styles.info; },
     }"
     x-on:toast.window="
        const toast = { id: Date.now() + Math.random(), duration: 4500, type: 'info', title: '', message: '', ...$event.detail };
        dynamic.push(toast);
        setTimeout(() => { dynamic = dynamic.filter(t => t.id !== toast.id); }, toast.duration);
     ">

    {{-- Toasts disparados por JS (fetch/AJAX) sin recargar la página --}}
    <template x-for="t in dynamic" :key="t.id">
        <div x-show="true" x-transition
             class="group relative w-full max-w-sm overflow-hidden rounded-2xl border-l-4 bg-white/90 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.12)]"
             :class="styleFor(t.type).border">
            <div class="p-4 flex items-start gap-4">
                <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full"
                     :class="[styleFor(t.type).bg, styleFor(t.type).icon]">
                    <x-heroicon-s-check-circle class="w-6 h-6" x-show="t.type === 'success'" />
                    <x-heroicon-s-exclamation-triangle class="w-6 h-6" x-show="t.type === 'error'" />
                    <x-heroicon-s-information-circle class="w-6 h-6" x-show="t.type === 'info'" />
                </div>
                <div class="flex-1 pt-0.5">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center justify-between">
                        <span x-text="t.title"></span>
                        <button @click="dynamic = dynamic.filter(x => x.id !== t.id)" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <x-heroicon-s-x-mark class="w-4 h-4" />
                        </button>
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 leading-relaxed font-medium" x-text="t.message"></p>
                </div>
            </div>
        </div>
    </template>

    @if (session('success'))
        <x-ui.toast-item type="success" title="¡Éxito!" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-ui.toast-item type="error" title="Atención" :message="session('error')" :duration="8000" />
    @endif

    @if (session('info'))
        <x-ui.toast-item type="info" title="Información" :message="session('info')" />
    @endif

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        @php
            $errorCount = $errors->count();
            $firstError = $errors->first();
            $title = $errorCount > 1 
                ? "Error de validación (+" . ($errorCount - 1) . " más)" 
                : "Error de validación";
        @endphp
        
        <x-ui.toast-item 
            type="error" 
            :title="$title"
            :message="$firstError"
            :duration="8000" 
        />
    @endif

</div>