<?php

namespace App\Livewire\Sales\Pos;

use Livewire\Component;
use App\Models\Products\Product;
use App\Models\Inventory\InventoryStock; // Importación necesaria

class QuoteSearch extends Component
{
    public $search = '';
    public $results = [];

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->results = [];
            return;
        }

        // Buscamos productos que tengan stock > 0
        $this->results = InventoryStock::whereHas('product', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->with('product:id,name,price,sku')
            ->where('quantity', '>', 0)
            ->take(8)
            ->get()
            ->map(function($stock) {
                return [
                    'id'    => $stock->product_id,
                    'name'  => $stock->product->name,
                    'price' => $stock->product->price,
                    'sku'   => $stock->product->sku,
                ];
            })->toArray();
    }

    public function selectProduct($productId)
    {
        // Emitimos el evento al componente padre (QuoteBuilder)
        $this->dispatch('product-selected', productId: $productId);
        
        $this->search = '';
        $this->results = [];
    }

    public function render()
    {
        // Asegúrate de que la vista esté en esta ruta:
        return view('livewire.sales.pos.quote-search'); 
    }
}