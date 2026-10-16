<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveInventoryCountRequest;
use App\Http\Requests\Inventory\StoreInventoryCountRequest;
use App\Models\Inventory\InventoryCount;
use App\Services\Inventory\InventoryCountPrintService;
use App\Services\Inventory\InventoryCountService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Toma física (v1.5.0 REQ-2.1). El listado y el modal de crear viven en
 * App\Livewire\App\Inventory\InventoryCountTable; aquí el show (revisión), la
 * pantalla de conteo, aplicar, cancelar y el PDF.
 */
class InventoryCountController extends Controller
{
    public function __construct(protected InventoryCountService $service) {}

    public function index()
    {
        return view('inventory.counts.index');
    }

    public function store(StoreInventoryCountRequest $request)
    {
        try {
            $count = $this->service->create($request->validated());
        } catch (DomainException $e) {
            // Como error del almacén: el modal se reabre con el mensaje bajo el campo.
            return back()->withInput()->withErrors(['warehouse_id' => $e->getMessage()]);
        }

        // Quien la crea sin permiso de contar (solo create) va al listado.
        if (! auth()->user()->can('inventory_counts.count')) {
            return redirect()->route('inventory.counts.index')->with('success', "Toma física {$count->number} creada.");
        }

        return redirect()->route('inventory.counts.count', $count)
            ->with('success', "Toma física {$count->number} creada. Ya puedes empezar a contar.");
    }

    public function show(InventoryCount $count)
    {
        $count->load([
            'warehouse:id,name', 'category:id,name',
            'creator:id,name', 'applier:id,name', 'canceler:id,name',
            'items' => fn ($q) => $q->with('product:id,name,sku,barcode,cost')
                ->join('products', 'products.id', '=', 'inventory_count_items.product_id')
                ->orderBy('products.name')
                ->select('inventory_count_items.*'),
        ]);

        return view('inventory.counts.show', [
            'count' => $count,
            'showComparison' => $count->differencesVisibleTo(auth()->user()),
        ]);
    }

    /** Pantalla de conteo (solo en borrador). */
    public function count(InventoryCount $count)
    {
        if (! $count->isDraft()) {
            return auth()->user()->can('inventory_counts.view')
                ? redirect()->route('inventory.counts.show', $count)
                : redirect()->route('inventory.counts.index')->with('info', "La toma {$count->number} ya no está en borrador.");
        }

        $count->load([
            'warehouse:id,name', 'category:id,name',
            'items' => fn ($q) => $q->with('product:id,name,sku,barcode')
                ->join('products', 'products.id', '=', 'inventory_count_items.product_id')
                ->orderBy('products.name')
                ->select('inventory_count_items.*'),
        ]);

        return view('inventory.counts.count', ['count' => $count]);
    }

    public function save(SaveInventoryCountRequest $request, InventoryCount $count)
    {
        try {
            $this->service->saveProgress($count, $request->validated('counts'), $request->validated('notes'));
        } catch (DomainException $e) {
            return redirect()->route('inventory.counts.index')->with('error', $e->getMessage());
        }

        $counted = $count->items()->whereNotNull('counted_quantity')->count();
        $total = $count->items()->count();

        // "Guardar avance" se queda contando; "Guardar y revisar" va al show (quien puede
        // verlo); "Guardar y terminar" (contador sin permiso de ver) vuelve al listado.
        $route = match ($request->input('then')) {
            'review' => auth()->user()->can('inventory_counts.view') ? 'inventory.counts.show' : 'inventory.counts.index',
            'done' => 'inventory.counts.index',
            default => 'inventory.counts.count',
        };

        return redirect()->route($route, $route === 'inventory.counts.index' ? [] : $count)
            ->with('success', "Avance guardado: {$counted} de {$total} productos contados.");
    }

    public function apply(InventoryCount $count)
    {
        try {
            $count = $this->service->apply($count);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.counts.show', $count)
            ->with('success', "Toma física {$count->number} aplicada: las existencias del almacén quedaron ajustadas.");
    }

    public function cancel(InventoryCount $count)
    {
        try {
            $this->service->cancel($count);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.counts.show', $count)
            ->with('success', "Toma física {$count->number} cancelada.");
    }

    /** ?download=1 descarga; sin él se abre en el navegador. */
    public function pdf(Request $request, InventoryCount $count, InventoryCountPrintService $print)
    {
        $pdf = $print->generateLetterPDF($count);

        return $request->boolean('download')
            ? $pdf->download("{$count->number}.pdf")
            : $pdf->stream("{$count->number}.pdf");
    }
}
