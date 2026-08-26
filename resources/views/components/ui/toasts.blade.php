<div x-data="toastManager()"
     @notify.window="addToast($event.detail)"
     @notify-redirect.window="saveForRedirect($event.detail)"
     @remove-toast.window="removeToast($event.detail)"
     class="fixed top-4 right-1 z-[100] flex flex-col items-end pointer-events-none sm:p-4">

    {{-- 1. Ingesta de Sesiones Clásicas de Laravel --}}
    <div class="hidden">
        @if (session('success'))
            <span x-init="addToast({ type: 'success', title: '¡Éxito!', message: {{ json_encode(session('success')) }} })"></span>
        @endif
        @if (session('error'))
            <span x-init="addToast({ type: 'error', title: 'Error', message: {{ json_encode(session('error')) }} })"></span>
        @endif
        @if (session('info'))
            <span x-init="addToast({ type: 'info', title: 'Información', message: {{ json_encode(session('info')) }} })"></span>
        @endif
        @if (session('warning'))
            <span x-init="addToast({ type: 'warning', title: 'Advertencia', message: {{ json_encode(session('warning')) }} })"></span>
        @endif
        @unless($suppressValidationToast ?? false)
            @if ($errors->any())
                @php
                    $errorCount = $errors->count();
                    $firstError = $errors->first();
                    $title = $errorCount > 1 ? "Error de validación (+" . ($errorCount - 1) . " más)" : "Error de validación";
                @endphp
                <span x-init="addToast({ type: 'error', title: {{ json_encode($title) }}, message: {{ json_encode($firstError) }} })"></span>
            @endif
        @endunless
    </div>

    {{--
        2. Pila de Toasts.
        Con 1-2 toasts: ambos se muestran completos y activos, apilados normalmente (el nuevo empuja al anterior hacia abajo).
        Con 3+ toasts: solo el más reciente queda abierto; el resto se acumula detrás como cartas (asomando debajo),
        y del 4to en adelante se agrupan en el chip contador.

        Nota: la animación de entrada/salida/apertura/colapso se controla en un único lugar (cardStyle, vía :style),
        sin x-transition de Alpine — mezclar los dos sistemas sobre las mismas propiedades (opacity/transform)
        provocaba que la tarjeta quedara con opacidad 0 tras la promoción.
    --}}
    {{-- pb-3 compensa el margin-bottom negativo de la última tarjeta colapsada, que si no reduce la altura
         del contenedor y hace que el chip de abajo se monte encima de esa tarjeta --}}
    <div class="w-full max-w-sm pb-3">
        <template x-for="(toast, i) in cascadeToasts" :key="toast.id">
            <div x-data="toastItem(toast)"
                 x-effect="setState(i, cascadeActive)"
                 @mouseenter="open && pause()"
                 @mouseleave="open && resume()"
                 @touchstart="onSwipeStart($event)"
                 @touchmove="onSwipeMove($event)"
                 @touchend="onSwipeEnd()"
                 class="relative w-full overflow-hidden rounded-xl border-l-[3px] bg-white"
                 :class="[config.bgClass, (show && open) ? 'pointer-events-auto shadow-lg' : 'pointer-events-none shadow-md']"
                 :style="cardStyle">

                <div class="p-4 flex items-start gap-3 transition-opacity duration-300" :class="open ? 'opacity-100' : 'opacity-0'">
                    <div class="flex-shrink-0 mt-0.5" :class="config.iconClass">
                        <div x-show="toast.type === 'success'"><x-heroicon-s-check-circle class="w-6 h-6" /></div>
                        <div x-show="toast.type === 'error'"><x-heroicon-s-x-circle class="w-6 h-6" /></div>
                        <div x-show="toast.type === 'warning'"><x-heroicon-s-exclamation-triangle class="w-6 h-6" /></div>
                        <div x-show="toast.type === 'info' || !toast.type"><x-heroicon-s-information-circle class="w-6 h-6" /></div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 leading-tight" x-text="toast.title"></h3>
                        <p class="mt-1 text-xs text-gray-600 font-medium leading-relaxed" x-text="toast.message"></p>
                    </div>

                    <button @click="close()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                        <x-heroicon-s-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Barra de Progreso — solo corre mientras está abierto --}}
                <div class="absolute bottom-0 left-0 w-full h-1 bg-black/5" x-show="open">
                    <div class="h-full transition-all ease-linear"
                        :class="config.progressClass"
                        :style="`width: ${percent}%`"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- 3. Chip de Agrupación — a partir del 4to toast --}}
    <button x-show="overflowCount > 0" x-cloak
            @click="dismissOverflow()"
            class="mt-2 pointer-events-auto flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-slate-800/90 text-white shadow-md hover:opacity-90 transition-all">
        <span>+<span x-text="overflowCount"></span> más</span>
        <span class="opacity-70 font-medium">· Descartar todo</span>
    </button>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastManager', () => ({
            toasts: [],
            init() {
                // Recuperar Toast pendiente si venimos de una redirección JS
                const pending = sessionStorage.getItem('zertix_pending_toast');
                if (pending) {
                    setTimeout(() => {
                        this.addToast(JSON.parse(pending));
                    }, 300); // Pequeño delay para que la animación se aprecie al cargar la página
                    sessionStorage.removeItem('zertix_pending_toast');
                }
            },
            // Hasta 3 tarjetas renderizadas, la más reciente al frente (índice 0).
            get cascadeToasts() { return this.toasts.slice(-3).reverse(); },
            // Con 1-2 toasts se muestran ambos normales. Al llegar el 3ro se activa la acumulación tipo cartas.
            get cascadeActive() { return this.toasts.length >= 3; },
            // El resto (4to en adelante) se agrupa en el chip contador.
            get overflowCount() { return Math.max(0, this.toasts.length - 3); },
            addToast(toast) {
                toast.id = Date.now() + Math.random();
                const defaultDurations = { success: 5000, info: 5000, warning: 7000, error: 10000 };
                toast.duration = toast.duration || defaultDurations[toast.type] || 5000;
                this.toasts.push(toast);
            },
            saveForRedirect(toast) {
                // Guarda en sesión del navegador en lugar de mostrarlo al instante
                sessionStorage.setItem('zertix_pending_toast', JSON.stringify(toast));
            },
            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts.splice(index, 1);
                }
            },
            dismissOverflow() {
                // Los agrupados nunca se mostraron corriendo su timer — se descartan de una vez
                this.toasts = this.toasts.slice(-3);
            }
        }));

        Alpine.data('toastItem', (toast) => ({
            show: false,           // false = entrando/saliendo (fuera de pantalla); true = montado en su lugar
            remaining: toast.duration,
            interval: null,
            paused: false,
            percent: 100,
            config: {},
            open: null,            // true = tarjeta completa y activa; false = acumulada detrás (carta asomando)
            position: 0,           // profundidad dentro de la pila (0 = frente)
            stackActive: false,    // copia local del cascadeActive del manager (nombre distinto para no taparlo por scope)
            touchStartX: 0,
            touchStartY: 0,
            swipeAxis: null,
            swipeOffset: 0,

            init() {
                this.setConfig();
                setTimeout(() => { this.show = true; }, 50);
            },
            setConfig() {
                const configs = {
                    success: {
                        bgClass: 'border-emerald-500 bg-emerald-50',
                        iconClass: 'text-emerald-500',
                        progressClass: 'bg-emerald-500'
                    },
                    error: {
                        bgClass: 'border-red-500 bg-red-50',
                        iconClass: 'text-red-500',
                        progressClass: 'bg-red-500'
                    },
                    warning: {
                        bgClass: 'border-amber-500 bg-amber-50',
                        iconClass: 'text-amber-500',
                        progressClass: 'bg-amber-500'
                    },
                    info: {
                        bgClass: 'border-blue-500 bg-blue-50',
                        iconClass: 'text-blue-500',
                        progressClass: 'bg-blue-500'
                    }
                };
                this.config = configs[toast.type] || configs.info;
            },
            // i = índice dentro de la pila renderizada (0 = más reciente). isCascadeActive viene del manager (3+ toasts).
            // Con cascada inactiva (1-2 toasts) todas quedan abiertas. Con cascada activa, solo el índice 0 queda abierto.
            setState(i, isCascadeActive) {
                this.position = i;
                this.stackActive = isCascadeActive;
                const shouldBeOpen = !isCascadeActive || i === 0;
                if (this.open === shouldBeOpen) return; // ya está en el estado correcto — no reinicia el timer
                this.open = shouldBeOpen;
                if (shouldBeOpen) this.startTimer();
                else this.stopTimer();
            },
            // Único punto de control de opacity/transform/max-height/margin para todos los estados:
            // entrando, abierta, acumulada (colapsada) y saliendo. Evita mezclar con x-transition de Alpine.
            get cardStyle() {
                if (!this.show) {
                    // Fuera de pantalla — estado inicial antes de entrar, y estado final antes de desmontar al cerrar.
                    return 'opacity: 0; transform: translateX(24px); max-height: 400px; margin-bottom: 0.75rem; transition: transform 0.4s ease, opacity 0.4s ease;';
                }
                if (this.open) {
                    const spacing = this.stackActive ? '0.375rem' : '0.75rem';
                    if (this.swipeOffset !== 0) {
                        const opacity = Math.max(0, 1 - Math.abs(this.swipeOffset) / 200);
                        return `opacity: ${opacity}; transform: translateX(${this.swipeOffset}px); max-height: 400px; margin-bottom: ${spacing}; transition: none;`;
                    }
                    return `opacity: 1; transform: translateX(0); max-height: 400px; margin-bottom: ${spacing}; transition: transform 0.3s ease, opacity 0.3s ease, max-height 0.35s ease, margin 0.35s ease;`;
                }
                const depth = this.position; // 1 o 2
                return `opacity: ${1 - depth * 0.12}; transform: translateY(${depth * 6}px) scale(${1 - depth * 0.035}); max-height: 14px; margin-bottom: -8px; z-index: ${10 - depth}; transition: max-height 0.35s ease, transform 0.35s ease, opacity 0.35s ease, margin 0.35s ease;`;
            },
            startTimer() {
                if (this.interval) return; // ya está corriendo
                this.paused = false;
                let lastTick = Date.now();
                this.interval = setInterval(() => {
                    if (!this.paused) {
                        const now = Date.now();
                        const delta = now - lastTick;
                        lastTick = now;
                        this.remaining -= delta;
                        this.percent = Math.max(0, (this.remaining / toast.duration) * 100);
                        if (this.remaining <= 0) this.close();
                    } else {
                        lastTick = Date.now(); // Prevenir saltos de tiempo al regresar de otra pestaña
                    }
                }, 10);
            },
            stopTimer() {
                clearInterval(this.interval);
                this.interval = null;
            },
            pause() { this.paused = true; },
            resume() { this.paused = false; },
            onSwipeStart(e) {
                if (!this.open) return;
                this.touchStartX = e.touches[0].clientX;
                this.touchStartY = e.touches[0].clientY;
                this.swipeAxis = null;
                this.pause();
            },
            onSwipeMove(e) {
                if (!this.open) return;
                const dx = e.touches[0].clientX - this.touchStartX;
                const dy = e.touches[0].clientY - this.touchStartY;
                if (!this.swipeAxis) {
                    // Determina el eje dominante una sola vez, para no competir con el scroll vertical
                    this.swipeAxis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
                }
                if (this.swipeAxis === 'x') {
                    this.swipeOffset = dx; // ambos sentidos permitidos
                }
            },
            onSwipeEnd() {
                if (!this.open) return;
                if (Math.abs(this.swipeOffset) > 100) {
                    this.close();
                } else {
                    this.swipeOffset = 0;
                    this.resume();
                }
            },
            close() {
                this.show = false;
                this.stopTimer();
                setTimeout(() => {
                    this.$dispatch('remove-toast', toast.id);
                }, 450); // Esperar que termine la transición de salida (0.4s)
            }
        }));
    });
</script>
