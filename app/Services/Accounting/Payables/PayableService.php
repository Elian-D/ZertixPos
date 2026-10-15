<?php

namespace App\Services\Accounting\Payables;

use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Models\Accounting\Payable;
use App\Models\Accounting\PayablePayment;
use App\Services\Accounting\Treasury\FinancialMovementService;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cuentas por pagar y sus pagos (v1.5.0 REQ-4.2 y REQ-4.3). Espejo de los cobros de CxC:
 * abono operativo, sin asiento contable (Decisión 4 de v1.5.0; el asiento iría en pay()).
 */
class PayableService
{
    public function __construct(protected FinancialMovementService $movements) {}

    /**
     * Crea la CxP de un documento: un gasto a crédito (§4.4) o, en la Fase 5, una recepción.
     *
     * @param  array{vendor_id: ?int, concept: string, total_amount: float, supplier_invoice?: ?string, issue_date: string, due_date: string}  $data
     */
    public function createFor(Model $source, array $data): Payable
    {
        return Payable::create([
            'vendor_id' => $data['vendor_id'] ?? null,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'concept' => $data['concept'],
            'total_amount' => round($data['total_amount'], 2),
            'balance' => round($data['total_amount'], 2),
            'supplier_invoice' => $data['supplier_invoice'] ?? null,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'status' => Payable::STATUS_PENDING,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Paga (total o parcial) desde una cuenta de Cajas y Bancos. Pagar a mano no puede dejar
     * la cuenta en negativo; el pago automático de una recepción de contado (Fase 5) pasa
     * $fromDocument = true: la mercancía ya entró y no se frena por el saldo.
     */
    public function pay(Payable $payable, FinancialAccount $account, float $amount, string $date, ?string $reference, ?string $notes, bool $fromDocument = false): PayablePayment
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new DomainException('El monto del pago debe ser mayor que 0.');
        }

        return DB::transaction(function () use ($payable, $account, $amount, $date, $reference, $notes, $fromDocument) {
            $payable = Payable::whereKey($payable->id)->lockForUpdate()->firstOrFail();

            if (! $payable->isOpen()) {
                throw new DomainException("La cuenta por pagar {$payable->number} está ".mb_strtolower($payable->status_label).': no admite pagos.');
            }

            if ($amount > (float) $payable->balance) {
                throw new DomainException('El pago no puede superar lo pendiente ('.number_format((float) $payable->balance, 2).').');
            }

            $payment = PayablePayment::create([
                'payable_id' => $payable->id,
                'financial_account_id' => $account->id,
                'amount' => $amount,
                'payment_date' => $date,
                'reference' => $reference,
                'notes' => $notes,
                'status' => PayablePayment::STATUS_ACTIVE,
                'created_by' => Auth::id(),
            ]);

            $this->movements->register(
                $account,
                FinancialMovement::DIRECTION_OUT,
                $amount,
                $payable->source_type === Expense::class ? FinancialMovement::ORIGIN_EXPENSE_PAYMENT : FinancialMovement::ORIGIN_SUPPLIER_PAYMENT,
                $date,
                [
                    'reference_type' => PayablePayment::class,
                    'reference_id' => $payment->id,
                    'notes' => "Pago {$payment->number} de {$payable->number}".($payable->vendor ? " · {$payable->vendor->display_name}" : ''),
                ],
                $fromDocument,
            );

            $this->applyBalance($payable, -$amount);

            return $payment;
        });
    }

    /**
     * Anula un pago: el dinero vuelve a la cuenta (reverso) y el monto vuelve a lo pendiente
     * de la CxP. El pago queda Anulado con su motivo; es la red de seguridad del "0 de más".
     */
    public function voidPayment(PayablePayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $payment = PayablePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->isVoided()) {
                throw new DomainException("El pago {$payment->number} ya está anulado.");
            }

            if ($movement = $payment->movement) {
                // Devuelve el dinero: nunca deja negativa la cuenta, y una cuenta inactiva lo acepta igual.
                $this->movements->reverse($movement, "Anulación del pago {$payment->number}: {$reason}", fromDocument: true);
            }

            $payment->update([
                'status' => PayablePayment::STATUS_VOIDED,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $payable = Payable::whereKey($payment->payable_id)->lockForUpdate()->firstOrFail();
            $this->applyBalance($payable, (float) $payment->amount);
        });
    }

    /**
     * Anula la CxP junto con su documento de origen (un gasto): primero cada pago no anulado,
     * con su reverso, y después la CxP. No se llama sola desde la UI.
     */
    public function voidWithSource(Payable $payable, string $reason): void
    {
        DB::transaction(function () use ($payable, $reason) {
            foreach ($payable->payments()->where('status', PayablePayment::STATUS_ACTIVE)->get() as $payment) {
                $this->voidPayment($payment, $reason);
            }

            $payable->refresh()->update([
                'status' => Payable::STATUS_VOIDED,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    /** Suma (o resta) al saldo y recalcula el estado. Una CxP anulada conserva su estado. */
    private function applyBalance(Payable $payable, float $delta): void
    {
        $balance = round((float) $payable->balance + $delta, 2);

        $status = match (true) {
            $payable->status === Payable::STATUS_VOIDED => Payable::STATUS_VOIDED,
            $balance <= 0 => Payable::STATUS_PAID,
            $balance < (float) $payable->total_amount => Payable::STATUS_PARTIAL,
            default => Payable::STATUS_PENDING,
        };

        $payable->update(['balance' => max($balance, 0), 'status' => $status]);
    }
}
