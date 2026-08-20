<?php

namespace App\Livewire\Sales\Pos\Pages;

use App\Models\Accounting\ClientCollection;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PosWorkspace extends Component
{
    public PosTerminal $terminal;

    public ?PosSession $session = null;

    public function mount(PosTerminal $pos_terminal)
    {
        $this->terminal = $pos_terminal;

        // Cualquier usuario con permiso para operar sesiones POS puede trabajar en un
        // turno ya abierto, no solo quien lo abrió (ver PosSession 9.0 en POS-Interfaz.md).
        abort_unless(Auth::user()->can('pos sessions manage'), 403);

        $this->session = PosSession::where('terminal_id', $pos_terminal->id)
            ->open()
            ->first();

        if (! $this->session) {
            session()->flash('error', 'No hay un turno abierto en esta terminal. Selecciona la terminal desde el lobby para abrirla.');

            return redirect()->route('sales.pos.index');
        }
    }

    /**
     * Catálogo COMPLETO de productos activos, con el stock del almacén de la
     * terminal como dato opcional (0 si nunca hubo movimiento en ese almacén).
     * Antes esto arrancaba desde InventoryStock, así que un producto sin fila
     * de stock en este almacén (aunque existiera y estuviera activo) no aparecía
     * en absoluto — el cajero no podía ni confirmar ni descartar que el negocio
     * lo vendiera. El catálogo debe mostrarse siempre completo; el stock es solo
     * información sobre disponibilidad, no un filtro de existencia.
     */
    private function getProducts(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->select('id', 'category_id', 'name', 'sku', 'price', 'image_path', 'is_stockable')
            ->with(['stocks' => function ($query) {
                $query->where('warehouse_id', $this->terminal->warehouse_id)
                    ->select('id', 'product_id', 'warehouse_id', 'quantity', 'min_stock');
            }])
            ->get();

        // Precargado en una sola query (evita N+1 de Product::taxRate() por producto):
        // suma de tasas apiladas por producto (Fase 5, REQ-5.1).
        $taxRates = DB::table('product_taxes')
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->sum(fn ($row) => config("impuestos.{$row->tax_key}.rate", 0)));

        return $products
            ->map(function ($product) use ($taxRates) {
                $stock = $product->stocks->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    // Suma de tasas apiladas — el carrito la snapshotea al agregar el
                    // producto, igual que el precio.
                    'tax_rate' => (float) ($taxRates->get($product->id) ?? 0),
                    'stock' => (float) ($stock?->quantity ?? 0),
                    'min_stock' => (float) ($stock?->min_stock ?? 0),
                    'is_stockable' => (bool) $product->is_stockable,
                    'category_id' => $product->category_id,
                    'image' => $product->image_url,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Clientes operativos, con "Consumidor Final" primero.
     */
    private function getClients(): array
    {
        return Client::where('is_active', true)
            ->select('id', 'name', 'tax_id', 'credit_limit', 'balance')
            ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'tax_id' => $client->tax_id,
                'available' => (float) ($client->credit_limit - $client->balance),
                'is_moroso' => $client->esMoroso(),
            ])
            ->toArray();
    }

    /**
     * Clientes con al menos una CxC pendiente (no pagada), cada uno con sus
     * Receivable individuales y su saldo — catálogo del selector de Cobro
     * (Fase 6, REQ-6.2). Consumidor Final se excluye explícitamente: nunca
     * tiene CxC real (no puede vender a crédito, ver REQ-2.3/5.13).
     */
    private function getDebtors(): array
    {
        return Client::where('id', '!=', 1)
            ->whereHas('receivables', fn ($q) => $q->whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL]))
            ->with(['receivables' => function ($q) {
                $q->whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
                    ->select('id', 'client_id', 'document_number', 'total_amount', 'current_balance', 'due_date')
                    // Orden = FIFO real (la más vieja primero) — el frontend solo deja
                    // seleccionar la primera de la lista, el resto queda bloqueada
                    // (Fase 6, REQ-6.3 extra: no se puede cobrar una factura nueva
                    // salteando una más vieja del mismo cliente).
                    ->orderBy('due_date');
            }])
            ->select('id', 'name', 'balance')
            // Orden ideal del listado: por monto de deuda, descendente — el cliente
            // que más debe aparece primero (feedback post-prueba en navegador).
            ->orderByDesc('balance')
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'balance' => (float) $client->balance,
                'receivables' => $client->receivables->map(fn ($r) => [
                    'id' => $r->id,
                    'document_number' => $r->document_number,
                    'total_amount' => (float) $r->total_amount,
                    'current_balance' => (float) $r->current_balance,
                    'due_date' => $r->due_date?->format('d/m/Y'),
                    // Vencida = hoy > due_date (Receivable::getIsOverdueAttribute()) — el
                    // cajero debe verlo de inmediato, no calcularlo a ojo contra la fecha.
                    'is_overdue' => $r->is_overdue,
                    // (int): Carbon::diffInDays() en Carbon 3 devuelve float (ej. 1.43) si no
                    // se trunca — el badge necesita días enteros ("VENCIDA — 1 DÍA").
                    'days_overdue' => $r->is_overdue ? (int) \Carbon\Carbon::parse($r->due_date)->startOfDay()->diffInDays(now()->startOfDay()) : 0,
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        $usaNcf = module_enabled('sales.ncf');
        $canCollect = module_enabled('sales.receivables') && $this->terminal->allow_receivable_collection;

        // pull() en vez de get(): se consume una sola vez, así que si el Workspace
        // llega a renderizarse más de una vez tras el checkout (doble request, doble
        // montaje de Alpine, etc.) el ticket de impresión automática no se abre dos veces.
        $lastSaleId = session()->pull('lastSaleId');
        $lastSale = $lastSaleId ? Sale::find($lastSaleId) : null;
        $lastInvoiceId = $lastSale?->invoice?->id;

        // Mismo mecanismo que lastSaleId/printUrl (Fase 6, REQ-6.8) — el ticket de
        // Cobro se autoimprime igual que el de Venta.
        $lastCollectionId = session()->pull('lastCollectionId');
        $lastCollection = $lastCollectionId ? ClientCollection::find($lastCollectionId) : null;

        $view = view('livewire.sales.pos.pages.pos-workspace', [
            'lastInvoiceId' => $lastInvoiceId,
            'lastSale' => $lastSale ? [
                'id' => $lastSale->id,
                'number' => $lastSale->number,
                'total' => (float) $lastSale->grand_total,
            ] : null,
            'lastCollectionId' => $lastCollection?->id,
            'products' => $this->getProducts(),
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'clients' => $this->getClients(),
            'debtors' => $canCollect ? $this->getDebtors() : [],
            'canCollect' => $canCollect,
            'tipoPagos' => TipoPago::sortByPriority(TipoPago::activo()->select('id', 'nombre', 'slug')->get()),
            'ncfTypes' => $usaNcf ? NcfType::where('is_active', true)->select('id', 'name', 'code', 'requires_rnc')->get() : collect(),
            'usaNcf' => $usaNcf,
            'posConfig' => pos_config(),
            'walkinClientId' => pos_config('default_walkin_customer_id') ?? 1,

            // Política de descuentos: 100% por terminal desde 11.2, sin fallback global,
            // con topes separados por ítem y global desde 11.2.5 (ver
            // PosTerminal::$fillable/$casts) — se leen directo del modelo actual.
            'maxItemDiscountPercentage' => $this->terminal->max_item_discount_percentage,
            'maxGlobalDiscountPercentage' => $this->terminal->max_global_discount_percentage,
            'allowItemDiscount' => $this->terminal->allow_item_discount,
            'allowGlobalDiscount' => $this->terminal->allow_global_discount,
        ]);

        return $view->layout('layouts.pos');
    }
}
