import './bootstrap';
// IMPORTANTE: Importamos ambos desde el bundle de Livewire
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;

Livewire.start();

// Loader de páginas
import './pages/clients'
import './pages/pos'
import './pages/equipments'
import './pages/equipmentsTypes'
import './pages/businessTypes'
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
import './pages/sales'
import './pages/invoices'
import './pages/sequences'
import './pages/ncf-logs'
import './pages/nfc-types'
import './pages/terminals'
import './pages/pos-sessions'
import './pages/pos-cash-movements'
import './pages/quotes'

