<?php

namespace App\Services\Accounting\Collection;

use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\ClientCollection;
use App\Models\Accounting\DocumentType;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Receivable;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Services\Accounting\Receivable\ReceivableService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionService
{
    public function __construct(
        protected JournalEntryService $journalService,
        protected ReceivableService $receivableService
    ) {}

    /**
     * Registra un nuevo Recibo de Cobro
     */
    public function createCollection(array $data): ClientCollection
    {
        return DB::transaction(function () use ($data) {
            $receivable = Receivable::findOrFail($data['receivable_id']);

            // 1. Correlativo 'PAG' — igual que el 'FAC' de SaleService, es numeración
            // operativa (talonario), no contabilidad formal. Corre siempre. El código
            // 'PAG' se mantiene tal cual (Opción A, REQ-4.2) — solo el name mostrado
            // del DocumentType cambia a "Recibo de Cobro", el prefijo impreso no se toca.
            $docType = DocumentType::where('code', 'PAG')->firstOrFail();
            $receiptNumber = $docType->getNextNumberFormatted();

            // 2. Abono operativo — corre SIEMPRE, sin depender de accounting.advanced.
            $payment = ClientCollection::create([
                'client_id' => $receivable->client_id,
                'receivable_id' => $receivable->id,
                'tipo_pago_id' => $data['tipo_pago_id'],
                'receipt_number' => $receiptNumber,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null, // Opcional (ej: No. Transferencia)
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'status' => ClientCollection::STATUS_ACTIVE,
                // Trazabilidad de sesión/terminal (Fase 6, REQ-6.6) — null si el Cobro
                // se originó desde backoffice (no viene en $data en ese caso).
                'pos_session_id' => $data['pos_session_id'] ?? null,
                'pos_terminal_id' => $data['pos_terminal_id'] ?? null,
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
                    'description' => "Cobro Recibido: {$receiptNumber} - Cliente: {$receivable->client->name}",
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
     * Anula un cobro realizado
     */
    public function cancelCollection(ClientCollection $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status === ClientCollection::STATUS_CANCELLED) {
                throw new \Exception('El cobro ya se encuentra anulado.');
            }

            // Guard: un cobro de backoffice nace ya contado — se genera su asiento
            // contable en la misma transacción de creación (createCollection()), sin
            // ningún turno/sesión que siga abierto después. A diferencia del POS, nunca
            // existe una ventana de corrección en caliente para este origen.
            // El destino real de esta corrección es el flujo de Devolución (todavía no
            // construido, ver roadmap) — el mensaje lo referencia como lo que se viene,
            // sin fingir que ya está disponible: mientras tanto, el paso real es
            // contabilidad. El frontend (REQ-6.12) ya evita que se llegue hasta acá en
            // el uso normal; esta excepción es el respaldo para un POST directo, así que
            // debe seguir siendo honesta por sí sola.
            if (is_null($payment->pos_session_id)) {
                throw new \Exception('Este cobro se registró desde backoffice y ya quedó contabilizado — no se puede anular. Este caso se resolverá con el flujo de Devolución.');
            }

            // Guard: un cobro cobrado en una sesión de caja ya cerrada ya quedó contado
            // en un arqueo impreso — revertirlo lo desactualizaría en silencio. Cancelar
            // solo es válido como corrección en caliente dentro del mismo turno abierto.
            if ($payment->posSession && $payment->posSession->isClosed()) {
                throw new \Exception('Este cobro pertenece a una sesión de caja ya cerrada y quedó contado en su arqueo — no se puede anular. Este caso se resolverá con el flujo de Devolución.');
            }

            // 1. Reversar el saldo en la factura
            $receivable = $payment->receivable;
            $receivable->current_balance += $payment->amount;
            $this->receivableService->updateStatusBasedOnBalance($receivable);

            // 2. Anular el asiento contable (si el JournalEntryService tiene esa lógica, o crear uno de reversión)
            if ($payment->journalEntry) {
                $payment->journalEntry->update(['status' => JournalEntry::STATUS_CANCELLED]);
            }

            // 3. Marcar cobro como anulado
            $payment->update(['status' => ClientCollection::STATUS_CANCELLED]);

            // 4. Refrescar balance del cliente
            $payment->client->refreshBalance();

            return true;
        });
    }
}
