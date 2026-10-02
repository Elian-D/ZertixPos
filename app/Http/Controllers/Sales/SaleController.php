<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleService;
use App\Services\Sales\SalesServices\SaleCatalogService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $service,
        protected SaleCatalogService $catalogService
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Sales\SaleTable.
     */
    public function index()
    {
        return view('sales.index');
    }

    /**
     * Redirigir a la impresión de la factura asociada a la venta.
     */
    public function printInvoice(Sale $sale, Request $request)
    {
        // Buscamos la factura asociada
        $invoice = $sale->invoice;

        if (!$invoice) {
            return back()->with('error', 'Esta venta aún no tiene una factura generada.');
        }

        // Pasar el formato y parámetros de descarga al método print del InvoiceController
        return app(\App\Http\Controllers\Sales\InvoiceController::class)->print($invoice, $request);
    }

    /**
     * Mostrar formulario de creación (Ventanilla de Venta).
     */
    public function create()
    {
        return view('sales.create', $this->catalogService->getForForm());
    }

    /**
     * Registrar la venta, afectar inventario y generar asientos.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->service->create($request->validated());

            return redirect()
                ->route('sales.index')
                ->with('success', "Venta #{$sale->number} registrada con éxito.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', "Error al procesar la venta: " . $e->getMessage());
        }
    }

    public function cancel(Request $request, Sale $sale)
    {
        // 1. Verificación de estado rápida
        if ($sale->status === Sale::STATUS_CANCELED) {
            return back()->with('error', "Esta venta ya ha sido anulada previamente.");
        }

        // 2. Validación — el motivo es obligatorio siempre (REQ-3.18): antes solo se
        // pedía con NCF y en las ventas sin NCF se descartaba sin guardarse.
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ], [
            'cancellation_reason.required' => 'Indica el motivo de la anulación.',
            'cancellation_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        try {
            // 3. Ejecución vía Servicio
            $this->service->cancel($sale, $validated['cancellation_reason']);

            return back()->with('success', "Venta {$sale->number} anulada y stock retornado.");
        } catch (Exception $e) {
            // Loguear el error para el admin es buena idea
            Log::error("Error anulando venta {$sale->id}: " . $e->getMessage());
            return back()->with('error', "Error: " . $e->getMessage());
        }
    }
}
