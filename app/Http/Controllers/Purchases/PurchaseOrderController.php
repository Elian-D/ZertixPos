<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\SavePurchaseOrderRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\Vendor;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\PurchasePrintService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Órdenes de compra (v1.5.0 REQ-5.1). El listado vive en App\Livewire\App\Purchases\PurchaseOrderTable;
 * recibir, en PurchaseReceptionController.
 */
class PurchaseOrderController extends Controller
{
    const HEADER_FIELDS = ['vendor_id', 'warehouse_id', 'order_date', 'expected_date', 'payment_condition', 'financial_account_id', 'notes'];

    public function __construct(protected PurchaseOrderService $service) {}

    public function index()
    {
        return view('purchases.orders.index');
    }

    /** ?vendor= la trae con el proveedor elegido (desde su show). */
    public function create(Request $request)
    {
        return view('purchases.orders.create', $this->formData() + [
            'selectedVendor' => $request->integer('vendor') ?: null,
        ]);
    }

    public function store(SavePurchaseOrderRequest $request)
    {
        try {
            $order = $this->service->create($request->safe()->only(self::HEADER_FIELDS), $request->validated('lines'));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('purchases.orders.show', $order)
            ->with('success', "Orden {$order->number} guardada en borrador.");
    }

    public function show(PurchaseOrder $order)
    {
        $order->load([
            'vendor', 'warehouse:id,name', 'account:id,name',
            'creator:id,name', 'approver:id,name', 'sender:id,name', 'canceler:id,name',
            'items.product:id,name,sku,barcode',
            'receptions' => fn ($q) => $q->with(['creator:id,name', 'payable:id,source_type,source_id,number,status,balance,due_date'])
                ->withSum('items as quantity_received_total', 'quantity_received')
                ->withSum('items as quantity_rejected_total', 'quantity_rejected')
                ->latest('id'),
        ]);

        return view('purchases.orders.show', ['order' => $order]);
    }

    public function edit(PurchaseOrder $order)
    {
        if (! $order->isDraft()) {
            return redirect()->route('purchases.orders.show', $order)
                ->with('error', "La orden {$order->number} ya no está en borrador.");
        }

        $order->load('items');

        return view('purchases.orders.edit', $this->formData() + ['order' => $order, 'selectedVendor' => null]);
    }

    public function update(SavePurchaseOrderRequest $request, PurchaseOrder $order)
    {
        try {
            $this->service->update($order, $request->safe()->only(self::HEADER_FIELDS), $request->validated('lines'));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('purchases.orders.show', $order)->with('success', "Orden {$order->number} actualizada.");
    }

    public function approve(PurchaseOrder $order)
    {
        return $this->run(fn () => $this->service->approve($order), $order,
            "Orden {$order->number} aprobada: ya se puede recibir.");
    }

    public function markSent(PurchaseOrder $order)
    {
        return $this->run(fn () => $this->service->markSent($order), $order,
            "Orden {$order->number} marcada como enviada al proveedor.");
    }

    public function cancel(PurchaseOrder $order)
    {
        return $this->run(fn () => $this->service->cancel($order), $order, "Orden {$order->number} cancelada.");
    }

    /** ?download=1 descarga; sin él se abre en el navegador. */
    public function pdf(Request $request, PurchaseOrder $order, PurchasePrintService $print)
    {
        $pdf = $print->order($order);

        return $request->boolean('download')
            ? $pdf->download("{$order->number}.pdf")
            : $pdf->stream("{$order->number}.pdf");
    }

    private function run(callable $action, PurchaseOrder $order, string $message)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.orders.show', $order)->with('success', $message);
    }

    private function formData(): array
    {
        return [
            'vendors' => Vendor::where('is_active', true)->orderBy('business_name')
                ->get(['id', 'business_name', 'trade_name', 'payment_condition', 'credit_days', 'default_financial_account_id']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name']),
            'conditions' => PurchaseOrder::getConditions(),
            'products' => Product::where('type', Product::TYPE_PRODUCT)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'sku', 'barcode', 'cost']),
        ];
    }
}
