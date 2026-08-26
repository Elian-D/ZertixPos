import { resolveLabel } from './utils';
import { applyFilters, clearAllFilters } from './filters'; // Importamos la función de limpieza

export const renderChips = (ctx, params) => {
    if (!ctx.chipsContainer) return;
    ctx.chipsContainer.innerHTML = '';

    const realFilterKeys = Object.keys(params).filter(key => 
        key !== 'per_page' && key !== 'columns[]'
    );

    realFilterKeys.forEach(key => {
        const config = ctx.config.chips[key] ?? {};
        const label = resolveLabel(config, params[key]);
        
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-zertix-primary-50 text-zertix-primary-700 border border-zertix-primary-100 hover:bg-zertix-primary-100 transition';
        chip.innerHTML = `${config.label ?? key}: ${label} ✕`;

        chip.onclick = () => {
            const input = ctx.form.querySelector(`[name="${key}"]`);
            if (input) {
                input.tagName === 'SELECT' ? input.selectedIndex = 0 : input.value = '';
            }
            applyFilters(ctx);
        };
        ctx.chipsContainer.appendChild(chip);
    });

    // --- CORRECCIÓN AQUÍ ---
    if (realFilterKeys.length > 0) {
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button'; // Siempre definir tipo para evitar submits accidentales
        clearBtn.textContent = 'Limpiar todo';
        clearBtn.className = 'text-xs text-red-500 hover:text-red-700 ml-2 font-medium transition';
        
        clearBtn.onclick = () => {
            clearAllFilters(ctx); // Llamamos a la función correcta pasando el contexto
        };
        
        ctx.chipsContainer.appendChild(clearBtn);
    }
};