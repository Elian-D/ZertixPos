<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryWasteRequest;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryWaste;
use App\Models\Inventory\InventoryWasteItem;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Services\Inventory\InventoryWastePrintService;
use App\Services\Inventory\InventoryWasteService;
use Exception;
use Illuminate\Http\Request;

/**
 * Mermas (v1.5.0 REQ-2.2). El listado vive en App\Livewire\App\Inventory\InventoryWasteTable;
 * aquí el formulario de registrar, el show, anular y el PDF.
 */
class InventoryWasteController extends Controller
{
    public function __construct(protected InventoryWasteService $service) {}

    public function index()
    {
        return view('inventory.wastes.index');
    }

    public function create()
    {
        return view('inventory.wastes.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('type', Product::TYPE_PRODUCT)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'sku', 'barcode', 'cost']),
            // Existencia por almacén y producto: la cantidad no puede superarla.
            'stocks' => InventoryStock::get(['warehouse_id', 'product_id', 'quantity'])
                ->groupBy('warehouse_id')
                ->map(fn ($rows) => $rows->mapWithKeys(fn ($s) => [$s->product_id => (float) $s->quantity])),
            'reasons' => InventoryWasteItem::getReasons(manual: true),
        ]);
    }

    public function store(StoreInventoryWasteRequest $request)
    {
        try {
            $waste = $this->service->create(
                $request->safe()->only(['warehouse_id', 'waste_date', 'notes']),
                $request->validated('lines'),
            );
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('inventory.wastes.show', $waste)
            ->with('success', "Merma {$waste->number} registrada: se dieron de baja las existencias.");
    }

    public function show(InventoryWaste $waste)
    {
        $waste->load([
            'warehouse:id,name', 'creator:id,name', 'voider:id,name', 'reference',
            'items' => fn ($q) => $q->with('product:id,name,sku,barcode'),
        ]);

        return view('inventory.wastes.show', ['waste' => $waste]);
    }

    public function void(Request $request, InventoryWaste $waste)
    {
        $data = $request->validate(['void_reason' => 'required|string|min:5|max:255'], [], ['void_reason' => 'motivo']);

        try {
            $this->service->void($waste, $data['void_reason']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.wastes.show', $waste)
            ->with('success', "Merma {$waste->number} anulada: las existencias regresaron al almacén.");
    }

    /** ?download=1 descarga; sin él se abre en el navegador. */
    public function pdf(Request $request, InventoryWaste $waste, InventoryWastePrintService $print)
    {
        $pdf = $print->generateLetterPDF($waste);

        return $request->boolean('download')
            ? $pdf->download("{$waste->number}.pdf")
            : $pdf->stream("{$waste->number}.pdf");
    }
}
