<?php

namespace App\Services\Accounting\Payment;

use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\AccountingAccountRole;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Services\Accounting\Receivable\ReceivableService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\DocumentType;

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
            
            // 1. Obtener y Generar Correlativo usando el nuevo código 'PAG'
            $docType = DocumentType::where('code', 'PAG')->firstOrFail();
            $receiptNumber = $docType->getNextNumberFormatted(); // Asumiendo que este método existe en tu modelo

            // 2. Crear Asiento Contable
            $entry = $this->journalService->create([
                'entry_date'  => $data['payment_date'],
                'reference'   => $receiptNumber,
                'description' => "Pago Recibido: {$receiptNumber} - Cliente: {$receivable->client->name}",
                'status'      => JournalEntry::STATUS_POSTED,
                'items'       => [
                    [
                        'accounting_account_id' => AccountingAccountRole::resolve('cash_default'),
                        'debit'  => $data['amount'],
                        'credit' => 0,
                        'note'   => "Cobro según {$receiptNumber}"
                    ],
                    [
                        'accounting_account_id' => $receivable->accounting_account_id,
                        'debit'  => 0,
                        'credit' => $data['amount'],
                        'note'   => "Aplicación a factura {$receivable->document_number}"
                    ]
                ]
            ]);

            // 3. Registrar el Pago
            $payment = Payment::create([
                'client_id'        => $receivable->client_id,
                'receivable_id'    => $receivable->id,
                'tipo_pago_id'     => $data['tipo_pago_id'],
                'journal_entry_id' => $entry->id,
                'receipt_number'   => $receiptNumber, 
                'amount'           => $data['amount'],
                'payment_date'     => $data['payment_date'],
                'reference'        => $data['reference'] ?? null, // Ahora es opcional (ej: No. Transferencia)
                'note'             => $data['note'] ?? null,
                'created_by'       => Auth::id(),
                'status'           => Payment::STATUS_ACTIVE
            ]);

            // 4. Incrementar correlativo
            $docType->increment('current_number');

            // 5. Actualizar la Factura y Cliente
            $receivable->current_balance -= $data['amount'];
            $this->receivableService->updateStatusBasedOnBalance($receivable);
            $receivable->client->refreshBalance();
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
                throw new \Exception("El pago ya se encuentra anulado.");
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