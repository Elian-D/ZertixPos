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

// Loader de páginas
import './pages/category'
import './pages/units'
import './pages/products'
import './pages/warehouses'
import './pages/stock'
import './pages/movements'
import './pages/accounts'
import './pages/journals'
import './pages/documentTypes'
import './pages/receivable'
import './pages/payment'
import './pages/invoices'
import './pages/sequences'
import './pages/ncf-logs'
import './pages/nfc-types'
import './pages/pos-cash-movements'

