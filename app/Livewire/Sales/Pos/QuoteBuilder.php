<?php

namespace App\Livewire\Sales\Pos;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Products\Product;
use App\Models\Clients\Client;
use App\Models\Sales\Quotes\Quote;
use App\Services\Sales\Quotes\QuoteService;
use App\Models\Inventory\InventoryStock; // Importado para la validación

class QuoteBuilder extends Component
{
    public $clientId;
    public $notes = '';
    
    public $items = [];
    
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
                    'discount_percentage' => 0, // Nuevo campo fase 5
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

        // 11.2: la política de descuentos ahora vive por terminal (PosTerminal), y las
        // cotizaciones de backoffice no están atadas a ninguna. Decisión explícita: sin
        // límite aquí (backoffice es de uso exclusivo de admins de confianza) — no hay
        // config global a la que "heredar" desde que se eliminó de pos_settings.
        $allowItemDiscount = true;
        $maxDiscountPct = 100;

        foreach ($this->items as $index => &$item) {
            $item['quantity'] = max(1, (float)$item['quantity']);
            
            // FASE 5: Validar Reglas de Descuento por Porcentaje
            if (!$allowItemDiscount) {
                $item['discount_percentage'] = 0;
                $item['discount_amount'] = 0;
            } else {
                // Forzar que el porcentaje esté entre 0 y 100
                $item['discount_percentage'] = max(0, min(100, (float)$item['discount_percentage']));
                
                // Si el porcentaje ingresado supera el permitido por la configuración del POS
                if ($item['discount_percentage'] > $maxDiscountPct) {
                    $item['discount_percentage'] = $maxDiscountPct;
                    $this->addError("items.{$index}.discount_percentage", "Máx. permitido: {$maxDiscountPct}%");
                }

                // Calcular el monto real en dinero ($) a partir del porcentaje ajustado
                $originalItemTotal = $item['price'] * $item['quantity'];
                $item['discount_amount'] = ($originalItemTotal * $item['discount_percentage']) / 100;
            }
            
            // Calcular subtotal del ítem seguro
            $itemSubtotal = ($item['price'] * $item['quantity']) - $item['discount_amount'];
            $item['subtotal'] = max(0, $itemSubtotal);

            // Acumular los totales generales de la cotización
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
            
            foreach ($quote->items as $item) {
                $this->items[] = [
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'discount_amount' => $item->discount_amount,
                    'discount_percentage' => $item->discount_percentage ?? 0,
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

        // NUEVO: Validación de Stock global preventiva
        $hasStockErrors = false;
        foreach ($this->items as $index => $item) {
            $totalStock = InventoryStock::where('product_id', $item['product_id'])->sum('quantity');
            
            if ($totalStock < $item['quantity']) {
                $this->addError("items.{$index}.quantity", "Stock global insuficiente. Disp: {$totalStock}.");
                $hasStockErrors = true;
            }
        }

        if ($hasStockErrors) {
            $this->addError('general', 'Verifique la disponibilidad de inventario en los ítems marcados.');
            return; // Bloqueamos el guardado si no hay stock
        }

        $data = [
            'client_id'  => $this->clientId,
            'notes'      => $this->notes,
            'items'      => $this->items,
            'origin'     => $this->quoteModel ? $this->quoteModel->origin : 'backoffice',
            'expires_at' => $this->quoteModel ? $this->quoteModel->expires_at : now()->addDays(15),
        ];

        try {
            if ($this->quoteModel) {
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
        return view('livewire.sales.pos.quote-builder', [
            'clients' => $this->getOperativeClients()
        ]);
    }
}