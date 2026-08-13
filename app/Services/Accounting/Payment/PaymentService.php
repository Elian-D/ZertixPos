<?php

namespace App\Services\Accounting\Payment;

use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\DocumentType;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Services\Accounting\Receivable\ReceivableService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected JournalEntryService $journalService,
        protected ReceivableService $receivableService
    ) {}

    /**
     * Registra un nuevo Recibo de Pago
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $receivable = Receivable::findOrFail($data['receivable_id']);

            // 1. Correlativo 'PAG' — igual que el 'FAC' de SaleService, es numeración
            // operativa (talonario), no contabilidad formal. Corre siempre.
            $docType = DocumentType::where('code', 'PAG')->firstOrFail();
            $receiptNumber = $docType->getNextNumberFormatted();

            // 2. Abono operativo — corre SIEMPRE, sin depender de accounting.advanced.
            $payment = Payment::create([
                'client_id' => $receivable->client_id,
                'receivable_id' => $receivable->id,
                'tipo_pago_id' => $data['tipo_pago_id'],
                'receipt_number' => $receiptNumber,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null, // Opcional (ej: No. Transferencia)
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'status' => Payment::STATUS_ACTIVE,
            ]);

            $docType->increment('current_number');

            $receivable->current_balance -= $data['amount'];
            $this->receivableService->updateStatusBasedOnBalance($receivable);
            $receivable->client->refreshBalance();

            // 3. Asiento contable derivado — SOLO si accounting.advanced está activo.
            if (module_enabled('accounting.advanced')) {
                $entry = $this->journalService->create([
                    'entry_date' => $data['payment_date'],
                    'reference' => $receiptNumber,
                    'description' => "Pago Recibido: {$receiptNumber} - Cliente: {$receivable->client->name}",
                    'status' => JournalEntry::STATUS_POSTED,
                    'items' => [
                        [
                            'accounting_account_id' => AccountingAccountRole::resolve('cash_default'),
                            'debit' => $data['amount'],
                            'credit' => 0,
                            'note' => "Cobro según {$receiptNumber}",
                        ],
                        [
                            'accounting_account_id' => $receivable->accounting_account_id,
                            'debit' => 0,
                            'credit' => $data['amount'],
                            'note' => "Aplicación a factura {$receivable->document_number}",
                        ],
                    ],
                ]);

                $payment->update(['journal_entry_id' => $entry->id]);
            }

            return $payment;
        });
    }

    /**
     * Anula un pago realizado
     */
    public function cancelPayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status === Payment::STATUS_CANCELLED) {
                throw new \Exception('El pago ya se encuentra anulado.');
            }

            // 1. Reversar el saldo en la factura
            $receivable = $payment->receivable;
            $receivable->current_balance += $payment->amount;
            $this->receivableService->updateStatusBasedOnBalance($receivable);

            // 2. Anular el asiento contable (si el JournalEntryService tiene esa lógica, o crear uno de reversión)
            if ($payment->journalEntry) {
                $payment->journalEntry->update(['status' => JournalEntry::STATUS_CANCELLED]);
            }

            // 3. Marcar pago como anulado
            $payment->update(['status' => Payment::STATUS_CANCELLED]);

            // 4. Refrescar balance del cliente
            $payment->client->refreshBalance();

            return true;
        });
    }
}
