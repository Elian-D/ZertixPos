<?php

namespace App\Livewire\Sales\Pos\Pages;

use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use Illuminate\Support\Facades\Auth;
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
        return Product::query()
            ->where('is_active', true)
            ->select('id', 'category_id', 'name', 'sku', 'price', 'image_path', 'is_stockable')
            ->with(['stocks' => function ($query) {
                $query->where('warehouse_id', $this->terminal->warehouse_id)
                    ->select('id', 'product_id', 'warehouse_id', 'quantity', 'min_stock');
            }])
            ->get()
            ->map(function ($product) {
                $stock = $product->stocks->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
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
        return Client::whereHas('estadoCliente.categoria', function ($query) {
            $query->whereIn('code', ['OPERATIVO', 'FINANCIERO_RESTRICTO']);
        })
            ->with('estadoCliente.categoria')
            ->select('id', 'name', 'tax_id', 'credit_limit', 'balance')
            ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'tax_id' => $client->tax_id,
                'available' => (float) ($client->credit_limit - $client->balance),
                'is_moroso' => in_array($client->estadoCliente?->categoria?->code, ['BLOQUEO_TOTAL', 'FINANCIERO_RESTRICTO']),
            ])
            ->toArray();
    }

    public function render()
    {
        $config = general_config();
        $usaNcf = (bool) ($config?->usa_ncf ?? false);

        // pull() en vez de get(): se consume una sola vez, así que si el Workspace
        // llega a renderizarse más de una vez tras el checkout (doble request, doble
        // montaje de Alpine, etc.) el ticket de impresión automática no se abre dos veces.
        $lastSaleId = session()->pull('lastSaleId');
        $lastSale = $lastSaleId ? Sale::find($lastSaleId) : null;
        $lastInvoiceId = $lastSale?->invoice?->id;

        $view = view('livewire.sales.pos.pages.pos-workspace', [
            'lastInvoiceId' => $lastInvoiceId,
            'lastSale' => $lastSale ? [
                'id' => $lastSale->id,
                'number' => $lastSale->number,
                'total' => (float) $lastSale->total_amount,
            ] : null,
            'products' => $this->getProducts(),
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'clients' => $this->getClients(),
            'tipoPagos' => TipoPago::sortByPriority(TipoPago::activo()->select('id', 'nombre', 'slug')->get()),
            'ncfTypes' => $usaNcf ? NcfType::where('is_active', true)->select('id', 'name', 'code', 'requires_rnc')->get() : collect(),
            'usaNcf' => $usaNcf,
            'taxRate' => (float) ($config?->impuesto?->valor ?? 0),
            'posConfig' => pos_config(),
            'walkinClientId' => pos_config('default_walkin_customer_id') ?? 1,
        ]);

        return $view->layout('layouts.pos');
    }
}
