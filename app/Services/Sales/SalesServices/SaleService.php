<?php

namespace App\Services\Sales\SalesServices;

use App\Models\Sales\{Sale, SalePayment}; // Importamos SalePayment
use App\Models\Accounting\{DocumentType, JournalEntry, AccountingAccount, Receivable};
use App\Models\Inventory\InventoryMovement;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Services\Accounting\Receivable\ReceivableService;
use Illuminate\Support\Facades\{DB, Auth};
use App\Services\Sales\InvoicesServices\InvoiceService; 
use App\Contracts\Sales\NcfGeneratorInterface;
use App\Models\Sales\Ncf\NcfLog;
use App\DTOs\Sales\PosContext; // Importamos el DTO
use App\Models\Configuration\TipoPago;
use App\Models\Sales\Ncf\NcfSequence;
use App\Models\Sales\Pos\PosSetting;
use Carbon\Carbon;
use Exception;

class SaleService
{
    public function __construct(
        protected InventoryMovementService $inventoryService,
        protected JournalEntryService $journalService,
        protected ReceivableService $receivableService,
        protected InvoiceService $invoiceService,
        protected NcfGeneratorInterface $ncfGenerator
    ) {}

    public function create(array $data, ?PosContext $context = null): Sale
    {
        $config = general_config();

        // 1. Validación NCF
        if ($config?->usa_ncf && isset($data['ncf_type_id'])) {
            $this->validateNcfSequence($data['ncf_type_id']);
        }

        // 2. Validación de descuentos contra pos_settings
        $this->validateDiscounts($data);

        return DB::transaction(function () use ($data, $context, $config) {
            $docType = DocumentType::where('code', 'FAC')->firstOrFail();
            $saleNumber = $docType->getNextNumberFormatted();
            $docType->increment('current_number');

            $saleDate = now(); // Por defecto ahora mismo (con hora)

            if (isset($data['sale_date'])) {
                $parsedDate = Carbon::parse($data['sale_date']);
                
                // Si la fecha enviada es hoy, usamos now() para tener la hora exacta.
                // Si es una fecha distinta (venta retroactiva), respetamos la fecha enviada.
                if (!$parsedDate->isToday()) {
                    $saleDate = $parsedDate;
                }
            }

            $warehouseId = $context ? $context->warehouse_id : $data['warehouse_id'];

            // 2. Crear Cabecera de Venta
            $sale = Sale::create([
                'document_type_id' => $docType->id,
                'number'           => $saleNumber,
                'client_id'        => $data['client_id'],
                'warehouse_id'     => $warehouseId,
                'user_id'          => Auth::id(),
                'sale_date'        => $saleDate,
                'total_amount'     => $data['total_amount'],
                'discount_total'   => $data['discount_total'] ?? 0,
                'payment_type'     => $data['payment_type'],
                'tipo_pago_id'     => $data['tipo_pago_id'] ?? null,
                'cash_received'    => $data['cash_received'] ?? 0,
                'cash_change'      => $data['cash_change'] ?? 0,
                'status'           => Sale::STATUS_COMPLETED,
                'pos_session_id'   => $context?->session_id,
                'pos_terminal_id'  => $context?->terminal_id,
            ]);

            // 3. Crear Items e Inventario
            foreach ($data['items'] as $item) {
                $discountAmount     = $item['discount_amount'] ?? 0;
                $discountPercentage = $item['discount_percentage'] ?? 0;
                $lineSubtotal       = ($item['quantity'] * $item['price']) - $discountAmount;

                $sale->items()->create([
                    'product_id'          => $item['product_id'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['price'],
                    'discount_amount'     => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'subtotal'            => $lineSubtotal,
                ]);

                $this->inventoryService->register([
                    'warehouse_id'   => $warehouseId,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'type'           => InventoryMovement::TYPE_OUTPUT,
                    'description'    => "Venta {$saleNumber}",
                    'reference_type' => Sale::class,
                    'reference_id'   => $sale->id,
                ]);
            }

            // 4. Procesar Pagos y CxC (Si es crédito, se genera CxC por el TOTAL)
            $this->processPayments($sale, $data, $context);

            // 5. Contabilidad
            $this->generateSaleAccountingEntry($sale, $context);

            // 6. Fiscal e Invoice
            if ($config?->usa_ncf && !empty($data['ncf_type_id'])) {
                $this->ncfGenerator->generate($sale, $data['ncf_type_id']);
            }

            $this->invoiceService->createFromSale($sale);

            return $sale;
        });
    }

    protected function processPayments(Sale $sale, array $data, ?PosContext $context): void
    {
        $payments = $data['payments'] ?? [];

        // Si la venta es de tipo crédito, ignoramos multipagos y creamos CxC única
        if ($sale->payment_type === 'credit') {
            $this->createReceivableEntry($sale, $sale->total_amount);
            return;
        }

        if (empty($payments)) {
            // Un solo pago (Efectivo/Transferencia/etc)
            $sale->payments()->create([
                'tipo_pago_id' => $sale->tipo_pago_id,
                'amount'       => $sale->total_amount,
            ]);
        } else {
            // Venta Multipago (Solo para contado)
            foreach ($payments as $p) {
                $sale->payments()->create([
                    'tipo_pago_id' => $p['tipo_pago_id'],
                    'amount'       => $p['amount'],
                    'reference'    => $p['reference'] ?? null,
                ]);
            }
        }
    }

    protected function generateSaleAccountingEntry(Sale $sale, ?PosContext $context): JournalEntry
    {
        $items = [];
        
        // CRÉDITO a Ingresos (4.1)
        $items[] = [
            'accounting_account_id' => AccountingAccount::where('code', '4.1')->first()->id,
            'debit'  => 0,
            'credit' => $sale->total_amount,
            'note'   => "Venta {$sale->number}"
        ];

        // DÉBITOS (Si es crédito va a la cuenta del cliente, si es contado a las cuentas de pago)
        if ($sale->payment_type === 'credit') {
            $items[] = [
                'accounting_account_id' => $sale->client->accounting_account_id ?? $this->getAccountIdByCode('1.1.02'),
                'debit'  => $sale->total_amount,
                'credit' => 0,
                'note'   => "Cuenta por Cobrar: {$sale->client->name}"
            ];
        } else {
            foreach ($sale->payments as $payment) {
                $tipo = $payment->tipoPago;
                $accountId = ($tipo->slug === 'efectivo' && $context) 
                             ? $context->cash_account_id 
                             : $tipo->accounting_account_id;

                $items[] = [
                    'accounting_account_id' => $accountId,
                    'debit'  => $payment->amount,
                    'credit' => 0,
                    'note'   => "Pago: {$tipo->nombre} " . ($context ? " (Terminal: {$context->terminal_id})" : "")
                ];
            }
        }

        return $this->journalService->create([
            'entry_date'  => $sale->sale_date,
            'reference'   => $sale->number,
            'description' => "Venta " . ($sale->payment_type === 'credit' ? "A CRÉDITO" : "CONTADO") . ": {$sale->number}",
            'status'      => JournalEntry::STATUS_POSTED,
            'items'       => $items
        ]);
    }

    protected function createReceivableEntry(Sale $sale, float $amount): void
    {
        $this->receivableService->createReceivable([
            'client_id'       => $sale->client_id,
            'total_amount'    => $amount,
            'emission_date'   => $sale->sale_date,
            'due_date'        => $sale->sale_date->copy()->addDays(30),
            'document_number' => $sale->number,
            'reference_type'  => Sale::class,
            'reference_id'    => $sale->id,
        ]);
    }

    private function validateDiscounts(array $data): void
    {
        $settings = PosSetting::getSettings();
        $max      = $settings->max_discount_percentage;

        // Descuento global
        if (!empty($data['discount_total']) && $data['discount_total'] > 0) {
            if (!$settings->allow_global_discount) {
                throw new Exception("Los descuentos globales no están habilitados.");
            }

            $subtotalBruto = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['price']);
            if ($subtotalBruto > 0) {
                $globalPct = ($data['discount_total'] / $subtotalBruto) * 100;
                if ($globalPct > $max) {
                    throw new Exception("El descuento global ({$globalPct}%) supera el límite permitido ({$max}%).");
                }
            }
        }

        // Descuentos por ítem
        foreach ($data['items'] ?? [] as $item) {
            $pct = $item['discount_percentage'] ?? 0;
            $amt = $item['discount_amount'] ?? 0;

            if (($pct > 0 || $amt > 0) && !$settings->allow_item_discount) {
                throw new Exception("Los descuentos por ítem no están habilitados.");
            }

            if ($pct > $max) {
                throw new Exception("Un descuento por ítem ({$pct}%) supera el límite permitido ({$max}%).");
            }
        }
    }

    private function validateNcfSequence($ncfTypeId) {
        $exists = NcfSequence::where('ncf_type_id', $ncfTypeId)
            ->where('status', NcfSequence::STATUS_ACTIVE)
            ->where('expiry_date', '>=', now())
            ->whereColumn('current', '<', 'to')
            ->exists();
        if (!$exists) throw new Exception("Error Fiscal: Secuencias agotadas.");
    }

    private function getAccountIdByCode(string $code) {
        return AccountingAccount::where('code', $code)->firstOrFail()->id;
    }

        public function cancel(Sale $sale, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($sale, $reason) {
            if ($sale->status === Sale::STATUS_CANCELED) {
                throw new Exception("La venta ya se encuentra anulada.");
            }

        // 1. Manejo de la reversión financiera (CxC) - Soporta Pagos Mixtos y Puros
        $receivables = Receivable::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->get();

        foreach ($receivables as $receivable) {
            if ($receivable->current_balance < $receivable->total_amount || $receivable->status === Receivable::STATUS_PAID) {
                throw new Exception("No se puede anular: Una de las cuentas por cobrar vinculadas ya tiene abonos.");
            }
            $this->receivableService->cancelReceivable($receivable);
        }
        
        // Actualizar el log del NCF con el motivo real
        NcfLog::where('sale_id', $sale->id)
            ->update([
                'status' => NcfLog::STATUS_VOIDED,
                'cancellation_reason' => $reason ?? 'Anulación de venta manual'
            ]);

            // 2. Reversión de Ingresos (4.1 vs Caja/CxC)
            $this->generateCancellationAccountingEntry($sale);

            // 3. REVERSIÓN DE COSTO Y STOCK
            foreach ($sale->items as $item) {
                $this->inventoryService->register([
                    'warehouse_id'   => $sale->warehouse_id,
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity, // Cantidad positiva para reingreso
                    'type'           => InventoryMovement::TYPE_ADJUSTMENT, // <--- CAMBIO CLAVE
                    'description'    => "Reversión de costo por anulación {$sale->number}",
                    'reference_type' => Sale::class,
                    'reference_id'   => $sale->id,
                ]);
            }

            $this->invoiceService->cancelInvoice($sale);
            
            return $sale->update(['status' => Sale::STATUS_CANCELED]);
        });
    }
    
    protected function generateCancellationAccountingEntry(Sale $sale)
    {
        // Buscamos el asiento original por la referencia (número de factura)
        $originalEntry = JournalEntry::where('reference', $sale->number)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();
        
        if ($originalEntry) {
            $this->journalService->reverse($originalEntry->id, [
                'entry_date' => now(),
                'description' => "Reversión por anulación de venta {$sale->number}"
            ]);
        }
    }
}