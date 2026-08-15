<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\Accounting\PaymentsExport;
use App\Filters\Accounting\PaymentsFilters\PaymentFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Payment\StorePaymentRequest;
use App\Models\Accounting\Payment;
use App\Services\Accounting\Payment\PaymentCatalogService;
use App\Services\Accounting\Payment\PaymentService;
use App\Tables\AccountingTables\PaymentTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected PaymentService $service,
        protected PaymentCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', PaymentTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        $payments = (new PaymentFilters($request))
            ->apply(Payment::query()->with(['client', 'receivable', 'tipoPago', 'creator']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('accounting.payments.partials.table', [
                'items' => $payments,
                'visibleColumns' => $visibleColumns,
                'allColumns' => PaymentTable::allColumns(),
                'defaultDesktop' => PaymentTable::defaultDesktop(),
                'defaultMobile' => PaymentTable::defaultMobile(),
            ])->render();
        }

        return view('accounting.payments.index', array_merge(
            [
                'items' => $payments,
                'visibleColumns' => $visibleColumns,
                'allColumns' => PaymentTable::allColumns(),
                'defaultDesktop' => PaymentTable::defaultDesktop(),
                'defaultMobile' => PaymentTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    public function print(Payment $payment)
    {
        try {
            // Importante: Cargamos 'receivable.reference' porque es polimórfica
            // y cargamos los items y productos de esa referencia
            $payment->load([
                'client',
                'creator',
                'tipoPago',
                'receivable.reference.items.product',
            ]);

            return view('accounting.payments.print', compact('payment'));
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo cargar el formato: '.$e->getMessage());
        }
    }

    /**
     * Exportar los datos filtrados a Excel
     */
    public function export(Request $request)
    {
        try {
            // Aplicamos los mismos filtros que en la tabla principal
            $query = (new PaymentFilters($request))
                ->apply(Payment::query());

            $fileName = 'reporte-pagos-'.now()->format('d-m-Y-H-i').'.xlsx';

            return Excel::download(new PaymentsExport($query), $fileName);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar el reporte: '.$e->getMessage());
        }
    }

    public function create()
    {
        return view('accounting.payments.create', $this->catalogService->getForForm());
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            $this->service->createPayment($request->validated());

            return redirect()
                ->route('finance.payments.index')
                ->with('success', 'Pago registrado y contabilizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancel(Payment $payment)
    {
        try {
            $this->service->cancelPayment($payment);

            return back()->with('success', 'El pago ha sido anulado y el saldo de la factura revertido.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === Payment::STATUS_ACTIVE) {
            return back()->with('error', 'No se puede eliminar un pago activo. Debe anularlo primero para revertir la contabilidad.');
        }

        return $this->destroyTrait($payment);
    }

    // Métodos requeridos por SoftDeletesTrait
    protected function getModelClass(): string
    {
        return Payment::class;
    }

    protected function getViewFolder(): string
    {
        return 'accounting.payments';
    }

    protected function getRouteIndex(): string
    {
        return 'finance.payments.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'finance.payments.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Pago / Recibo';
    }
}
