import './bootstrap';

// Alpine/Livewire NO se importan ni se arrancan acá (Fase 7.9). El intento
// anterior importaba Alpine desde el bundle de Livewire y llamaba
// Livewire.start() a mano vía @livewireScriptConfig, con un guard manual
// (window.__zertixLivewireStarted) para evitar un doble arranque — parcheaba
// el síntoma (TypeError: Cannot redefine property: $persist, que dejaba a
// Alpine sin terminar de quitar x-cloak del DOM y causaba el FOUC) pero no la
// causa: ese patrón manual es inherentemente frágil ante cualquier
// re-evaluación del módulo. Orvian (mismo patrón base de esta fase) nunca
// arranca Livewire a mano — usa @livewireScripts, la directiva estándar de
// Livewire que ya trae Alpine embebido y garantiza un único arranque por su
// cuenta. Todos los layouts (app-layout, layouts/pos, layouts/install) usan
// @livewireScripts en vez de @livewireScriptConfig; window.Alpine queda
// definido por ese mismo script para cualquier código que lo necesite.

// resources/js/pages/ (el wiring AjaxDataTable{tableId,formId,chips} del motor
// AJAX viejo) y resources/js/components/ajax-datatable/ se purgaron completos
// (2026-08-27, cierre de REQ-0.10) — el último módulo que los usaba de verdad
// ya migró al motor Livewire. Las tablas que siguen pendientes (Asientos
// Contables, Plan de Cuentas, Tipos NCF, Movimientos de Caja) ya tenían su UI
// de filtros rota desde que `x-data-table.*` se reclamó para Livewire (ver
// CLAUDE.md, "Antes de tocar cualquier tabla/listado") — no dependían de este
// JS para nada funcional. Ver docs/ui/datatable-migration-checklist.md §11
// para el detalle de qué falta migrar y dónde vive cada una.
