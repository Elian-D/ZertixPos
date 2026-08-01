<?php

namespace App\Services\Accounting\Receivable;

use App\Models\Accounting\{AccountingAccount, Receivable, JournalEntry};
use App\Models\Clients\Client;
use App\Models\Sales\Sale;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use Illuminate\Support\Facades\DB;
use Exception;

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
                ?? $this->getAccountIdByCode('1.1.02');

            return Receivable::create([
                'client_id'             => $data['client_id'],
                'journal_entry_id'      => null, // El asiento ya lo creó SaleService
                'accounting_account_id' => $receivableAccountId,
                'document_number'       => $data['document_number'],
                'description'           => $data['description'] ?? "CxC Venta: {$data['document_number']}",
                'total_amount'          => $data['total_amount'],
                'current_balance'       => $data['total_amount'],
                'emission_date'         => $data['emission_date'],
                'due_date'              => $data['due_date'],
                'reference_type'        => Sale::class,
                'reference_id'          => $data['reference_id'],
                'status'                => Receivable::STATUS_UNPAID,
            ]);
        });
    }

    /**
     * Anula una cuenta por cobrar (Solo si no tiene abonos)
     */
    public function cancelReceivable(Receivable $receivable): bool
    {
        return DB::transaction(function () use ($receivable) {
            if ($receivable->status === Receivable::STATUS_CANCELLED) return true;

            if ($receivable->current_balance < $receivable->total_amount) {
                throw new Exception("No se puede anular una factura con abonos.");
            }
            
            return $receivable->update([
                'status' => Receivable::STATUS_CANCELLED,
                'current_balance' => 0
            ]);
        });
    }

    /**
     * ACTUALIZA EL ESTADO BASADO EN EL SALDO
     * Este método es REQUERIDO por PaymentService al registrar abonos.
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

    protected function getAccountIdByCode(string $code): int
    {
        return AccountingAccount::where('code', $code)->firstOrFail()->id;
    }
}