<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\ReceivePurchaseOrderRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseReception;
use App\Models\Purchases\PurchaseReceptionItem;
use App\Services\Purchases\PurchasePrintService;
use App\Services\Purchases\PurchaseReceptionService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Recepciones de compra (v1.5.0 REQ-5.2 a REQ-5.4). El listado vive en
 * App\Livewire\App\Purchases\PurchaseReceptionTable. Se reciben desde la orden.
 */
class PurchaseReceptionController extends Controller
{
    public function __construct(protected PurchaseReceptionService $service) {}

    public function index()
    {
        return view('purchases.receptions.index');
    }

    /** Vista de recepción: por defecto propone recibir todo lo pendiente, al costo de la orden. */
    public function create(PurchaseOrder $order)
    {
        if (! $order->canReceive()) {
            return redirect()->route('purchases.orders.show', $order)
                ->with('error', "La orden {$order->number} no se puede recibir: está ".mb_strtolower($order->status_label).'.');
        }

        $order->load(['vendor', 'warehouse:id,name', 'items.product:id,name,sku,barcode']);

        return view('purchases.receptions.create', [
            'order' => $order,
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name', 'current_balance']),
            'reasons' => PurchaseReceptionItem::getRejectReasons(),
            'ratings' => PurchaseReception::getRatingLabels(),
        ]);
    }

    public function store(ReceivePurchaseOrderRequest $request, PurchaseOrder $order)
    {
        try {
            $reception = $this->service->receive(
                $order,
                $request->safe()->except('lines'),
                $request->validated('lines'),
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        $reception->load('payable:id,source_type,source_id,number');
        $order->refresh();

        $message = "Recepción {$reception->number} guardada: la orden quedó ".mb_strtolower($order->status_label).'.';
        if ($reception->payable) {
            $message .= $order->isCash()
                ? " Se pagó de contado ({$reception->payable->number})."
                : " Nació la cuenta por pagar {$reception->payable->number}.";
        }

        return redirect()->route('purchases.receptions.show', $reception)->with('success', $message);
    }

    public function show(PurchaseReception $reception)
    {
        $reception->load([
            'order.vendor', 'order.warehouse:id,name', 'account:id,name', 'creator:id,name',
            'items.product:id,name,sku,barcode', 'items.orderItem:id,unit_cost',
            'payable.payments' => fn ($q) => $q->with('account:id,name')->latest('id'),
        ]);

        return view('purchases.receptions.show', ['reception' => $reception]);
    }

    /** ?download=1 descarga; sin él se abre en el navegador. */
    public function pdf(Request $request, PurchaseReception $reception, PurchasePrintService $print)
    {
        $pdf = $print->reception($reception);

        return $request->boolean('download')
            ? $pdf->download("{$reception->number}.pdf")
            : $pdf->stream("{$reception->number}.pdf");
    }
}
