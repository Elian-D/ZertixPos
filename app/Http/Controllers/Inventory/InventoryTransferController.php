<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveInventoryTransferRequest;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Services\Inventory\InventoryTransferPrintService;
use App\Services\Inventory\InventoryTransferService;
use Exception;
use Illuminate\Http\Request;

/**
 * Transferencias entre almacenes (v1.5.0 REQ-2.4). El listado vive en
 * App\Livewire\App\Inventory\InventoryTransferTable.
 */
class InventoryTransferController extends Controller
{
    public function __construct(protected InventoryTransferService $service) {}

    public function index()
    {
        return view('inventory.transfers.index');
    }

    public function create()
    {
        return view('inventory.transfers.create', $this->formData());
    }

    public function store(SaveInventoryTransferRequest $request)
    {
        try {
            $transfer = $this->service->create(
                $request->safe()->only(['from_warehouse_id', 'to_warehouse_id', 'notes']),
                $request->validated('lines'),
            );
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Transferencia {$transfer->number} guardada en borrador.");
    }

    public function show(InventoryTransfer $transfer)
    {
        $transfer->load([
            'fromWarehouse:id,name', 'toWarehouse:id,name',
            'creator:id,name', 'sender:id,name', 'receiver:id,name', 'canceler:id,name',
            'items.product:id,name,sku,barcode',
            'waste:id,number,status,reference_type,reference_id',
        ]);

        // Existencia actual en el origen: en borrador avisa qué no alcanzaría al enviar.
        $originStock = $transfer->isDraft()
            ? InventoryStock::where('warehouse_id', $transfer->from_warehouse_id)
                ->whereIn('product_id', $transfer->items->pluck('product_id'))
                ->pluck('quantity', 'product_id')
            : collect();

        return view('inventory.transfers.show', compact('transfer', 'originStock'));
    }

    public function edit(InventoryTransfer $transfer)
    {
        if (! $transfer->isDraft()) {
            return redirect()->route('inventory.transfers.show', $transfer)
                ->with('error', "La transferencia {$transfer->number} ya no está en borrador.");
        }

        $transfer->load('items');

        return view('inventory.transfers.edit', array_merge(['transfer' => $transfer], $this->formData()));
    }

    public function update(SaveInventoryTransferRequest $request, InventoryTransfer $transfer)
    {
        try {
            $this->service->update(
                $transfer,
                $request->safe()->only(['from_warehouse_id', 'to_warehouse_id', 'notes']),
                $request->validated('lines'),
            );
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Transferencia {$transfer->number} actualizada.");
    }

    public function send(InventoryTransfer $transfer)
    {
        try {
            $this->service->send($transfer);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Transferencia {$transfer->number} enviada: la mercancía está en tránsito.");
    }

    /** Pantalla de recepción: cantidad recibida por línea (por defecto, la enviada). */
    public function receiveForm(InventoryTransfer $transfer)
    {
        if (! $transfer->isSent()) {
            return redirect()->route('inventory.transfers.show', $transfer)
                ->with('error', "La transferencia {$transfer->number} no está en tránsito.");
        }

        $transfer->load(['fromWarehouse:id,name', 'toWarehouse:id,name', 'sender:id,name', 'items.product:id,name,sku,barcode,cost']);

        return view('inventory.transfers.receive', compact('transfer'));
    }

    public function receive(Request $request, InventoryTransfer $transfer)
    {
        $data = $request->validate([
            'received' => 'required|array',
            'received.*' => 'nullable|numeric|min:0',
        ], [], ['received.*' => 'cantidad recibida']);

        try {
            $this->service->receive($transfer, $data['received']);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $transfer->refresh()->load('waste:id,number,reference_type,reference_id');
        $message = "Transferencia {$transfer->number} recibida.";
        if ($transfer->waste) {
            $message .= " Lo que no llegó quedó en la merma {$transfer->waste->number}.";
        }

        return redirect()->route('inventory.transfers.show', $transfer)->with('success', $message);
    }

    public function cancel(InventoryTransfer $transfer)
    {
        try {
            $this->service->cancel($transfer);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Transferencia {$transfer->number} cancelada.");
    }

    /** ?download=1 descarga; sin él se abre en el navegador. */
    public function pdf(Request $request, InventoryTransfer $transfer, InventoryTransferPrintService $print)
    {
        $pdf = $print->generateLetterPDF($transfer);

        return $request->boolean('download')
            ? $pdf->download("{$transfer->number}.pdf")
            : $pdf->stream("{$transfer->number}.pdf");
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('type', Product::TYPE_PRODUCT)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'sku', 'barcode']),
            // Existencia por almacén y producto: la del origen se muestra bajo cada línea.
            'stocks' => InventoryStock::get(['warehouse_id', 'product_id', 'quantity'])
                ->groupBy('warehouse_id')
                ->map(fn ($rows) => $rows->mapWithKeys(fn ($s) => [$s->product_id => (float) $s->quantity])),
        ];
    }
}
