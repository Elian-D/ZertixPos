<?php

namespace App\Services\Accounting\Payables;

use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Models\Purchases\Vendor;
use App\Services\Accounting\Treasury\FinancialMovementService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Gastos directos (v1.5.0 REQ-4.4). Sin aprobaciones ni adjuntos: solo el registro del
 * dinero y su efecto en la cuenta.
 */
class ExpenseService
{
    public function __construct(
        protected FinancialMovementService $movements,
        protected PayableService $payables,
    ) {}

    /**
     * De contado, el dinero sale de la cuenta al guardar (no puede dejarla en negativo: es una
     * acción manual). A crédito nace su CxP, con vencimiento = el elegido o la fecha del gasto
     * más los días de crédito del proveedor.
     *
     * @param  array{vendor_id?: ?int, category: string, concept: string, amount: float, expense_date: string, payment_condition: string, financial_account_id?: ?int, due_date?: ?string, supplier_invoice?: ?string, notes?: ?string}  $data
     */
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $cash = $data['payment_condition'] === Expense::CONDITION_CASH;

            $expense = Expense::create([
                'vendor_id' => $data['vendor_id'] ?? null,
                'category' => $data['category'],
                'concept' => $data['concept'],
                'amount' => round((float) $data['amount'], 2),
                'expense_date' => $data['expense_date'],
                'payment_condition' => $data['payment_condition'],
                'financial_account_id' => $cash ? $data['financial_account_id'] : null,
                'supplier_invoice' => $data['supplier_invoice'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Expense::STATUS_APPLIED,
                'created_by' => Auth::id(),
            ]);

            if ($cash) {
                $this->movements->register(
                    FinancialAccount::findOrFail($data['financial_account_id']),
                    FinancialMovement::DIRECTION_OUT,
                    (float) $expense->amount,
                    FinancialMovement::ORIGIN_EXPENSE_PAYMENT,
                    $expense->expense_date->toDateString(),
                    [
                        'reference_type' => Expense::class,
                        'reference_id' => $expense->id,
                        'notes' => "Gasto {$expense->number} · {$expense->category_label} · {$expense->concept}",
                    ],
                );
            } else {
                $vendor = $expense->vendor_id ? Vendor::find($expense->vendor_id) : null;

                $this->payables->createFor($expense, [
                    'vendor_id' => $expense->vendor_id,
                    'concept' => $expense->concept,
                    'total_amount' => (float) $expense->amount,
                    'supplier_invoice' => $expense->supplier_invoice,
                    'issue_date' => $expense->expense_date->toDateString(),
                    'due_date' => $data['due_date'] ?? Carbon::parse($expense->expense_date)->addDays($vendor?->credit_days ?? 0)->toDateString(),
                ]);
            }

            return $expense;
        });
    }

    /**
     * Anula el gasto: de contado, el dinero vuelve a la cuenta (reverso); a crédito, se anula
     * su CxP con cada pago y su reverso. Todo en una transacción.
     */
    public function void(Expense $expense, string $reason): Expense
    {
        return DB::transaction(function () use ($expense, $reason) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($expense->isVoided()) {
                throw new DomainException('Este gasto ya está anulado.');
            }

            if ($expense->isCash()) {
                if ($movement = $expense->movement) {
                    $this->movements->reverse($movement, "Anulación del gasto: {$reason}", fromDocument: true);
                }
            } elseif ($payable = $expense->payable) {
                $this->payables->voidWithSource($payable, $reason);
            }

            $expense->update([
                'status' => Expense::STATUS_VOIDED,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $expense;
        });
    }
}
