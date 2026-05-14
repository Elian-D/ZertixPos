<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Products\Product;
use App\Models\Clients\Client;
use App\Models\Sales\Quotes\Quote;
use App\Services\Sales\Quotes\QuoteService;

class QuoteBuilder extends Component
{
    // Estado del formulario
    public $clientId;
    public $notes = '';
    
    // El Carrito
    public $items = [];
    
    // Totales visuales
    public $subtotal = 0;
    public $discountTotal = 0;
    public $total = 0;

    public ?Quote $quoteModel = null;

    #[On('product-selected')] 
    public function addProduct($productId)
    {
        $existingIndex = collect($this->items)->search(fn($item) => $item['product_id'] == $productId);

        if ($existingIndex !== false) {
            $this->items[$existingIndex]['quantity']++;
        } else {
            $product = Product::find($productId);
            if ($product) {
                $this->items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'subtotal' => $product->price
                ];
            }
        }
        $this->recalculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }

    public function updatedItems()
    {
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->subtotal = 0;
        $this->discountTotal = 0;

        foreach ($this->items as &$item) {
            $item['quantity'] = max(1, (float)$item['quantity']);
            $item['discount_amount'] = max(0, (float)$item['discount_amount']);
            
            $itemSubtotal = ($item['price'] * $item['quantity']) - $item['discount_amount'];
            $item['subtotal'] = max(0, $itemSubtotal);

            $this->subtotal += ($item['price'] * $item['quantity']);
            $this->discountTotal += $item['discount_amount'];
        }

        $this->total = $this->subtotal - $this->discountTotal;
    }


    public function mount(?Quote $quote = null)
    {
        if ($quote && $quote->exists) {
            $this->quoteModel = $quote;
            $this->clientId = $quote->customer_id;
            $this->notes = $quote->notes;
            
            // Cargar items existentes
            foreach ($quote->items as $item) {
                $this->items[] = [
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'discount_amount' => $item->discount_amount,
                    'subtotal' => $item->subtotal
                ];
            }
            $this->recalculateTotals();
        }
    }


    public function saveQuote(QuoteService $quoteService)
    {
        $this->validate([
            'clientId' => 'required|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $data = [
            'client_id'  => $this->clientId,
            'notes'      => $this->notes,
            'items'      => $this->items,
            'origin'     => $this->quoteModel ? $this->quoteModel->origin : 'backoffice',
            'expires_at' => $this->quoteModel ? $this->quoteModel->expires_at : now()->addDays(15),
            // Importante: No pasamos status aquí para que el Service decida
        ];

        try {
            if ($this->quoteModel) {
                // USAR EL SERVICIO PARA ACTUALIZAR (Refresca items, totales, etc)
                $quoteService->update($this->quoteModel, $data);
                session()->flash('success', 'Cotización #' . $this->quoteModel->id . ' actualizada.');
            } else {
                $quote = $quoteService->store($data);
                session()->flash('success', 'Cotización #' . $quote->id . ' creada.');
            }

            return redirect()->route('sales.quotes.index');
            
        } catch (\Exception $e) {
            $this->addError('general', 'Error al procesar: ' . $e->getMessage());
        }
    }

    /**
     * Obtenemos los clientes operativos usando la lógica de estados
     */
    private function getOperativeClients()
    {
        return Client::whereHas('estadoCliente.categoria', function ($query) {
                $query->whereIn('code', ['OPERATIVO', 'FINANCIERO_RESTRICTO']);
            })
            ->select('id', 'name', 'tax_id') 
            ->orderByRaw("CASE WHEN name = 'Consumidor Final' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.pos.quote-builder', [
            'clients' => $this->getOperativeClients()
        ]);
    }
}