<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\PayPayableRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use App\Models\Accounting\PayablePayment;
use App\Services\Accounting\Payables\PayablePrintService;
use App\Services\Accounting\Payables\PayableService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Cuentas por pagar y sus pagos (v1.5.0 REQ-4.2 y REQ-4.3). Los listados viven en
 * App\Livewire\App\Finance\PayableTable y PayablePaymentTable; aquí el show, la vista de
 * pagar, anular un pago y el recibo PDF.
 */
class PayableController extends Controller
{
    public function __construct(protected PayableService $service) {}

    public function index()
    {
        return view('finance.payables.index');
    }

    public function show(Payable $payable)
    {
        $payable->load(['vendor', 'source', 'creator:id,name', 'voider:id,name',
            'payments' => fn ($q) => $q->with(['account:id,name', 'creator:id,name', 'voider:id,name'])->latest('id')]);

        return view('finance.payables.show', ['payable' => $payable]);
    }

    public function payForm(Payable $payable)
    {
        abort_unless($payable->isOpen(), 404);

        $payable->load('vendor');

        return view('finance.payables.pay', [
            'payable' => $payable,
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name', 'type', 'current_balance']),
            // La cuenta de pago sugerida del proveedor, si está activa.
            'suggested' => $payable->vendor?->default_financial_account_id,
        ]);
    }

    public function pay(PayPayableRequest $request, Payable $payable)
    {
        try {
            $payment = $this->service->pay(
                $payable,
                FinancialAccount::findOrFail($request->validated('financial_account_id')),
                (float) $request->validated('amount'),
                $request->validated('payment_date'),
                $request->validated('reference'),
                $request->validated('notes'),
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('finance.payables.show', $payable)
            ->with('success', "Pago {$payment->number} registrado.");
    }

    public function payments()
    {
        return view('finance.payables.payments');
    }

    public function voidPayment(Request $request, PayablePayment $payment)
    {
        $data = $request->validate(['void_reason' => 'required|string|min:5|max:255'], [], ['void_reason' => 'motivo']);

        try {
            $this->service->voidPayment($payment, $data['void_reason']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pago {$payment->number} anulado: el dinero volvió a la cuenta.");
    }

    /** Recibo de pago en PDF carta. ?download=1 descarga; sin él se abre en el navegador. */
    public function receipt(Request $request, PayablePayment $payment, PayablePrintService $print)
    {
        $pdf = $print->paymentReceipt($payment);

        return $request->boolean('download')
            ? $pdf->download("{$payment->number}.pdf")
            : $pdf->stream("{$payment->number}.pdf");
    }
}
