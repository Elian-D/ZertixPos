<?php

namespace App\Services\Accounting\Receivable;

use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Sales\Sale;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use Exception;
use Illuminate\Support\Facades\DB;

class ReceivableService
{
    public function __construct(
        protected JournalEntryService $journalService
    ) {}

    /**
     * Crea el registro de CxC vinculado opcionalmente a un asiento existente.
     */
    public function createReceivable(array $data): Receivable
    {
        return DB::transaction(function () use ($data) {
            $client = Client::findOrFail($data['client_id']);

            $receivableAccountId = $client->accounting_account_id
                ?? AccountingAccountRole::resolve('receivable_default');

            return Receivable::create([
                'client_id' => $data['client_id'],
                'journal_entry_id' => null, // El asiento ya lo creó SaleService
                'accounting_account_id' => $receivableAccountId,
                'document_number' => $data['document_number'],
                'description' => $data['description'] ?? "CxC Venta: {$data['document_number']}",
                'total_amount' => $data['total_amount'],
                'current_balance' => $data['total_amount'],
                'emission_date' => $data['emission_date'],
                'due_date' => $data['due_date'],
                'reference_type' => Sale::class,
                'reference_id' => $data['reference_id'],
                'status' => Receivable::STATUS_UNPAID,
            ]);
        });
    }

    /**
     * Anula una cuenta por cobrar (solo si nunca tuvo abonos).
     *
     * Guard endurecido (Fase 6, REQ-6.11): antes comparaba `current_balance <
     * total_amount` — un saldo mutable, no un hecho histórico. Con dos abonos de
     * 500 sobre una CxC de 1000, cancelar el abono #1 dejaba `current_balance =
     * 500` (bloqueaba bien), pero cancelar también el #2 devolvía el saldo a 1000
     * y este guard dejaba de bloquear, aunque sí hubo dinero real en caja en
     * algún momento — cancelando los abonos uno por uno (permiso `cancel
     * payments`) se llegaba al mismo resultado que este guard existe para
     * impedir (permiso separado `cancel receivables`). `collections()->exists()`
     * mira el hecho (¿alguna vez hubo un cobro, activo o cancelado?), no el saldo.
     */
    public function cancelReceivable(Receivable $receivable): bool
    {
        return DB::transaction(function () use ($receivable) {
            if ($receivable->status === Receivable::STATUS_CANCELLED) {
                return true;
            }

            if ($receivable->collections()->exists()) {
                throw new Exception('Esta cuenta ya tuvo abonos aplicados y ya quedó contabilizada — no se puede anular. Este caso se resolverá con el flujo de Devolución.');
            }

            return $receivable->update([
                'status' => Receivable::STATUS_CANCELLED,
                'current_balance' => 0,
            ]);
        });
    }

    /**
     * Baja la deuda por una devolución de venta a crédito (v1.4.0 Fase 2).
     * Rechaza si el monto excede el saldo pendiente — no se contempla devolver
     * el excedente.
     */
    public function reduceBalance(Receivable $receivable, float $amount): void
    {
        if ($receivable->status === Receivable::STATUS_CANCELLED) {
            throw new Exception('La cuenta por cobrar de esta venta está anulada.');
        }

        if (round($amount, 2) > round((float) $receivable->current_balance, 2)) {
            throw new Exception('El monto a devolver (RD$'.number_format($amount, 2).') excede la deuda pendiente del cliente (RD$'.number_format($receivable->current_balance, 2).').');
        }

        // La venta vale menos: baja la deuda original y el saldo juntos, así
        // "Total Abonado" (total - saldo) sigue reflejando solo cobros reales.
        $receivable->total_amount = round($receivable->total_amount - $amount, 2);
        $receivable->current_balance = round($receivable->current_balance - $amount, 2);

        // Devuelta toda la deuda sin ningún abono: no se "pagó", se anuló.
        if ($receivable->current_balance <= 0 && ! $receivable->collections()->exists()) {
            $receivable->status = Receivable::STATUS_CANCELLED;
            $receivable->save();

            return;
        }

        $this->updateStatusBasedOnBalance($receivable);
    }

    /**
     * Reversa de reduceBalance() al anular una devolución.
     */
    public function increaseBalance(Receivable $receivable, float $amount): void
    {
        $receivable->total_amount = round($receivable->total_amount + $amount, 2);
        $receivable->current_balance = round($receivable->current_balance + $amount, 2);
        $this->updateStatusBasedOnBalance($receivable);
    }

    /**
     * ACTUALIZA EL ESTADO BASADO EN EL SALDO
     * Este método es REQUERIDO por CollectionService al registrar abonos.
     */
    public function updateStatusBasedOnBalance(Receivable $receivable): void
    {
        if ($receivable->current_balance <= 0) {
            $receivable->status = Receivable::STATUS_PAID;
        } elseif ($receivable->current_balance < $receivable->total_amount) {
            $receivable->status = Receivable::STATUS_PARTIAL;
        } else {
            $receivable->status = Receivable::STATUS_UNPAID;
        }

        $receivable->save();
    }
}
