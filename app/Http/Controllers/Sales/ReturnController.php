<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Returns\SaleReturn;
use App\Services\Sales\Returns\ReturnPrintService;
use App\Services\Sales\Returns\ReturnService;
use Exception;
use Illuminate\Http\Request;

/**
 * Devoluciones (v1.4.0 Fase 2). Crear vive en el modal Livewire ReturnForm; el
 * listado en ReturnTable. Aquí solo el wrapper del index, el show, el ticket y
 * la anulación desde el show.
 */
class ReturnController extends Controller
{
    public function index()
    {
        return view('sales.returns.index');
    }

    public function show(SaleReturn $return)
    {
        $return->load([
            'sale.client:id,name,commercial_name,tax_id',
            'user:id,name',
            'voidedBy:id,name',
            'items.saleItem.product:id,name,sku,type',
            'exchangeSale.items.product:id,name',
            'exchangeSale.payments.tipoPago',
            'waste:id,number,status,reference_type,reference_id', // v1.5.0 REQ-2.3
        ]);

        return view('sales.returns.show', ['return' => $return]);
    }

    /**
     * ?preview=1 → solo el ticket, sin wrapper ni impresión automática (iframe del
     * show). ?download=1 → PDF. Sin parámetros → wrapper que imprime solo.
     */
    public function print(Request $request, SaleReturn $return, ReturnPrintService $printService)
    {
        if ($request->boolean('download')) {
            return $printService->downloadTicketPdf($return);
        }

        if ($request->boolean('preview')) {
            return $printService->getTicketView($return);
        }

        return view('sales.returns.print', [
            'return' => $return,
            'view' => $printService->getTicketView($return)->render(),
        ]);
    }

    public function void(SaleReturn $return, ReturnService $service)
    {
        try {
            $service->void($return);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Devolución {$return->number} anulada.");
    }
}
