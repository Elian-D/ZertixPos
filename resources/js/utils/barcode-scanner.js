/**
 * Lector de código de barras sin Enter (v1.5.0, docs/features/v1.5.0.md §1.2).
 *
 * Muchos lectores solo "teclean" el código, sin Enter al final. Un lector manda cada
 * carácter a 5–30 ms del anterior; una persona rara vez baja de 80–100 ms. Una ráfaga
 * de al menos `minLength` caracteres a ≤ `keyGap` ms, seguida de una pausa de `idle`
 * ms, se trata como un escaneo.
 *
 * Escucha en dos lugares:
 *  - `inputs`: los buscadores donde se escribe a mano (atributo [data-scan-input]).
 *    Detecta la ráfaga por eventos `input`; pegar (Ctrl+V) no cuenta.
 *  - La página entera, cuando el foco está FUERA de un campo editable (carrito, botón,
 *    lista). No usa un input oculto: le robaría el foco a los demás campos.
 *
 * Se pausa con un modal abierto (x-modal pone overflow-y-hidden en el body) o cuando
 * `isPaused()` devuelve true.
 *
 * onScan(code) debe devolver true si encontró el código. Si no:
 *  - en un buscador, se prueba solo lo que llegó en la ráfaga (el usuario ya había
 *    escrito algo antes de escanear) y si tampoco, el texto se queda filtrando;
 *  - fuera de un campo, se llama a onMiss(code) (ej. dejarlo en el buscador).
 *
 * Uso:
 *   const scanner = window.ZertixScanner.create({ onScan, onMiss, inputs: [...] });
 *   scanner.destroy();
 */
export function createBarcodeScanner({
    onScan,
    onMiss = () => {},
    inputs = [],
    isPaused = () => false,
    minLength = 6,
    keyGap = 35,
    idle = 100,
} = {}) {
    const paused = () => document.body.classList.contains('overflow-y-hidden') || isPaused();
    const cleanups = [];

    // --- Ráfaga dentro de un buscador ---
    inputs.forEach((input) => {
        const state = { last: 0, streak: 0, start: 0, timer: null };

        const onInput = (event) => {
            const now = performance.now();
            clearTimeout(state.timer);

            if (paused() || event.inputType !== 'insertText') {
                // Pegado, borrado, autocompletado: corta cualquier ráfaga en curso.
                state.streak = 0;
                state.last = now;
                return;
            }

            if (now - state.last <= keyGap && state.streak > 0) {
                state.streak++;
            } else {
                // Primer carácter de una posible ráfaga: desde aquí empieza el código.
                state.streak = 1;
                state.start = Math.max(0, input.value.length - 1);
            }
            state.last = now;

            if (state.streak < minLength) return;

            state.timer = setTimeout(() => {
                if (state.streak < minLength) return;
                const full = input.value;
                onScan(full) || onScan(full.slice(state.start));
                state.streak = 0;
            }, idle);
        };

        input.addEventListener('input', onInput);
        cleanups.push(() => {
            clearTimeout(state.timer);
            input.removeEventListener('input', onInput);
        });
    });

    // --- Ráfaga con el foco fuera de un campo ---
    const buf = { text: '', last: 0, timer: null };
    const reset = () => {
        clearTimeout(buf.timer);
        buf.text = '';
    };
    const flush = () => {
        const code = buf.text;
        reset();
        if (code.length < minLength) return false;
        if (!onScan(code)) onMiss(code);
        return true;
    };

    const onKeydown = (e) => {
        const t = e.target;
        const editable = t instanceof HTMLElement
            && (t.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName));
        if (editable || paused()) {
            reset();
            return;
        }

        const now = performance.now();

        if (e.key === 'Enter') {
            // Lector con sufijo Enter: se escanea y se evita que el Enter "pulse" el
            // botón que tenga el foco (ej. Cobrar).
            if (buf.text && now - buf.last <= idle && flush()) {
                e.preventDefault();
                e.stopPropagation();
            }
            return;
        }

        if (e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey) return;

        buf.text = (now - buf.last <= keyGap) ? buf.text + e.key : e.key;
        buf.last = now;

        clearTimeout(buf.timer);
        buf.timer = setTimeout(flush, idle);
    };

    window.addEventListener('keydown', onKeydown, true);
    cleanups.push(() => {
        reset();
        window.removeEventListener('keydown', onKeydown, true);
    });

    return {
        destroy() {
            cleanups.forEach((fn) => fn());
        },
    };
}

window.ZertixScanner = { create: createBarcodeScanner };
