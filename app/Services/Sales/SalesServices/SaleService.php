<?php

namespace App\Services\Sales\SalesServices;

use App\Contracts\Sales\NcfGeneratorInterface;
use App\DTOs\Sales\PosContext;
use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\DocumentType;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Inventory\InventoryMovement;
use App\Models\Products\Product;
use App\Models\Sales\Ncf\NcfLog;
use App\Models\Sales\Ncf\NcfSequence;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Services\Accounting\Receivable\ReceivableService;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Sales\InvoicesServices\InvoiceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class SaleService
 * * Servicio central para la orquestación del ciclo de vida de Ventas y Facturación (ZertixPOS).
 * Coordina de forma atómica la salida de inventarios, procesamiento de cobros o cuentas por cobrar (CxC),
 * generación de asientos de doble entrada en el Libro Mayor, cumplimiento fiscal dominicano (NCF) y logs de POS.
 */
class SaleService
{
    /**
     * Inyección de dependencias para desacoplamiento de submódulos core.
     */
    public function __construct(
        protected InventoryMovementService $inventoryService,
        protected JournalEntryService $journalService,
        protected ReceivableService $receivableService,
        protected InvoiceService $invoiceService,
        protected NcfGeneratorInterface $ncfGenerator
    ) {}

    /**
     * Orquesta la creación completa de una venta dentro de una transacción base de datos.
     *
     * * @param array $data Datos validados de la cabecera y líneas de venta.
     * @param  PosContext|null  $context  DTO opcional con metadatos de sesión y terminal POS.
     *
     * @throws Exception Si fallan las validaciones previas o reglas de negocio dentro de la transacción.
     */
    public function create(array $data, ?PosContext $context = null): Sale
    {
        // 1. VALIDACIÓN PRE-TRANSACCIONAL (Fail-Fast)
        // Se valida la secuencia NCF antes de abrir la transacción para evitar bloqueos innecesarios en base de datos.
        if (module_enabled('sales.ncf') && isset($data['ncf_type_id'])) {
            $this->validateNcfSequence($data['ncf_type_id']);
        }

        // Verifica políticas de descuento corporativo configuradas para el POS.
        $this->validateDiscounts($data, $context);

        // 2. ATOMICIDAD DEL FLUJO DE NEGOCIO
        // Se encapsula todo en una transacción SQL para asegurar que si un submódulo (v.g. Contabilidad o NCF)
        // falla, no se alteren inventarios ni se creen cabeceras de ventas huérfanas.
        return DB::transaction(function () use ($data, $context) {

            // Consumo y formateo secuencial de numeración interna por tipo de documento (Factura de Venta)
            $docType = DocumentType::where('code', 'FAC')->firstOrFail();
            $saleNumber = $docType->getNextNumberFormatted();
            $docType->increment('current_number');

            $saleDate = now();
            if (isset($data['sale_date'])) {
                $parsedDate = Carbon::parse($data['sale_date']);
                if (! $parsedDate->isToday()) {
                    $saleDate = $parsedDate; // Permite retrofechar facturas desde el Backoffice si aplica
                }
            }

            // Polimorfismo de origen: Si la venta viene de terminal física (POS), hereda su almacén asignado.
            $warehouseId = $context ? $context->warehouse_id : $data['warehouse_id'];

            // RNC capturado al vuelo para Crédito Fiscal cuando el cliente no tenía uno en archivo
            // (ej. walk-in verificado contra la DGII en el momento). Se guarda en el cliente para
            // que quede disponible en futuras ventas, sin sobrescribir un RNC ya existente.
            if (! empty($data['client_rnc'])) {
                $client = Client::find($data['client_id']);
                if ($client && empty($client->tax_id)) {
                    $client->update(['tax_id' => $data['client_rnc']]);
                }
            }

            // 3. PERSISTENCIA DE CABECERA
            $sale = Sale::create([
                'document_type_id' => $docType->id,
                'number' => $saleNumber,
                'client_id' => $data['client_id'],
                'warehouse_id' => $warehouseId,
                'user_id' => Auth::id(),
                'sale_date' => $saleDate,
                'total_amount' => $data['total_amount'],
                'discount_total' => $data['discount_total'] ?? 0,
                'payment_type' => $data['payment_type'],
                'tipo_pago_id' => $data['tipo_pago_id'] ?? null,
                'cash_received' => $data['cash_received'] ?? 0,
                'cash_change' => $data['cash_change'] ?? 0,
                'status' => Sale::STATUS_COMPLETED,

                // Campos de Integración POS (Trazabilidad y cuadre de cajas)
                'pos_session_id' => $context?->session_id,
                'pos_terminal_id' => $context?->terminal_id,
                'sale_origin' => $context?->sale_origin ?? 'backoffice',
                'is_walkin_customer' => $context?->is_walkin_customer ?? false,
            ]);

            // 4. PROCESAMIENTO DE ÍTEMS Y MOVIMIENTOS DE INVENTARIO
            // Precargado en una sola query: un "servicio" (is_stockable=false) no debe
            // generar movimiento de inventario ni asiento de Costo de Ventas — no tiene
            // cantidad física real que descontar de ningún almacén.
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->get(['id', 'is_stockable'])
                ->keyBy('id');

            foreach ($data['items'] as $item) {
                $discountAmount = $item['discount_amount'] ?? 0;
                $discountPercentage = $item['discount_percentage'] ?? 0;
                $lineSubtotal = ($item['quantity'] * $item['price']) - $discountAmount;

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'discount_amount' => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'subtotal' => $lineSubtotal,
                ]);

                $isStockable = $products->get($item['product_id'])?->is_stockable ?? true;

                if ($isStockable) {
                    // Registrar salida física de almacén (Kardex / Costeo) con referencia cruzada al modelo Sale
                    $this->inventoryService->register([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'type' => InventoryMovement::TYPE_OUTPUT,
                        'description' => "Venta {$saleNumber}",
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                    ]);
                }
            }

            // 5. INTEGRACIÓN CON MÓDULOS FINANCIEROS Y FISCALES
            // Registra cobros inmediatos o genera pasivos corrientes en cuentas por cobrar (CxC).
            $this->processPayments($sale, $data, $context);

            // Genera asiento contable automático de doble entrada en el Libro Diario.
            $this->generateSaleAccountingEntry($sale, $context);

            // Capa Fiscal: Si está activo el control dominicano, consume y asigna secuencia NCF válida.
            if (module_enabled('sales.ncf') && ! empty($data['ncf_type_id'])) {
                $this->ncfGenerator->generate($sale, $data['ncf_type_id']);
            }

            // Inicializa la entidad Invoice (Factura imprimible / PDF / Ticket) vinculada.
            $this->invoiceService->createFromSale($sale);

            return $sale;
        });
    }

    /**
     * Evalúa las formas de pago de la venta y bifurca la lógica entre Crédito Comercial o Venta de Contado.
     */
    protected function processPayments(Sale $sale, array $data, ?PosContext $context): void
    {
        $payments = $data['payments'] ?? [];

        // Flujo Crédito: No genera movimientos de efectivo, se envía directo al auxiliar de CxC.
        if ($sale->payment_type === 'credit') {
            $this->createReceivableEntry($sale, $sale->total_amount);

            return;
        }

        // Flujo Contado: Soporta transacciones con método de pago único o dividido (Multi-Pay).
        if (empty($payments)) {
            // Fallback de compatibilidad retroactiva para cajas tradicionales de método de pago único.
            $sale->payments()->create([
                'tipo_pago_id' => $sale->tipo_pago_id,
                'amount' => $sale->total_amount,
                'reference' => $data['payment_reference'] ?? null,
            ]);
        } else {
            // Registro detallado de cobros múltiples (v.g. Parte en Tarjeta y parte en Efectivo).
            foreach ($payments as $p) {
                $sale->payments()->create([
                    'tipo_pago_id' => $p['tipo_pago_id'],
                    'amount' => $p['amount'],
                    'reference' => $p['reference'] ?? null,
                ]);
            }
        }
    }

    /**
     * Genera de manera estricta el asiento de diario contable (Doble Entrada) para la venta actual.
     * Balancea los Créditos (Ingresos) contra los Débitos correspondientes (Caja física, Bancos o CxC).
     */
    protected function generateSaleAccountingEntry(Sale $sale, ?PosContext $context): ?JournalEntry
    {
        // Sin contabilidad avanzada, la venta no necesita asiento — Sale + Receivable/Payment
        // ya bastan para reportar (ver REQ-02.8/03.4). Evita depender de cuentas contables
        // que pueden no existir en esta instalación.
        if (! module_enabled('accounting.advanced')) {
            return null;
        }

        $items = [];

        // Entrada de Ingreso (Origen Crédito: Aumenta los ingresos por ventas en la cuenta de rol 'sales_revenue')
        $items[] = [
            'accounting_account_id' => AccountingAccountRole::resolve('sales_revenue'),
            'debit' => 0,
            'credit' => $sale->total_amount,
            'note' => "Venta {$sale->number}",
        ];

        if ($sale->payment_type === 'credit') {
            // Origen Débito para Ventas a Crédito: Aumenta la cuenta por cobrar del Cliente o la genérica (rol 'receivable_default')
            $items[] = [
                'accounting_account_id' => $sale->client->accounting_account_id ?? AccountingAccountRole::resolve('receivable_default'),
                'debit' => $sale->total_amount,
                'credit' => 0,
                'note' => "Cuenta por Cobrar: {$sale->client->name}",
            ];
        } else {
            // Origen Débito para Contado: Se itera cada método de pago recibido.
            foreach ($sale->payments as $payment) {
                $tipo = $payment->tipoPago;

                // Regla Crítica POS: Si es efectivo en caja física, el dinero entra a la cuenta específica de la terminal ($context)
                // de lo contrario, usa la cuenta contable global por defecto de la forma de pago (v.g. Banco / Pasarela de Tarjetas).
                // Ambas pueden ser null si accounting.advanced no está activo — ver REQ-02.9/02.8.
                $accountId = ($tipo->slug === 'efectivo' && $context?->cash_account_id)
                             ? $context->cash_account_id
                             : $tipo->accounting_account_id;

                $items[] = [
                    'accounting_account_id' => $accountId,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'note' => "Pago: {$tipo->nombre} ".($context ? " (Terminal: {$context->terminal_id})" : ''),
                ];
            }
        }

        // Envía los datos listos al JournalService, el cual valida internamente que Débito === Crédito antes de guardar.
        return $this->journalService->create([
            'entry_date' => $sale->sale_date,
            'reference' => $sale->number,
            'description' => 'Venta '.($sale->payment_type === 'credit' ? 'A CRÉDITO' : 'CONTADO').": {$sale->number}",
            'status' => JournalEntry::STATUS_POSTED,
            'items' => $items,
        ]);
    }

    /**
     * Genera la cuenta por cobrar en el módulo de créditos con vencimiento con base al plazo del cliente.
     */
    protected function createReceivableEntry(Sale $sale, float $amount): void
    {
        $plazo = $sale->client->payment_terms ?? 0;

        $this->receivableService->createReceivable([
            'client_id' => $sale->client_id,
            'total_amount' => $amount,
            'emission_date' => $sale->sale_date,
            'due_date' => $sale->sale_date->copy()->addDays($plazo),
            'document_number' => $sale->number,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }

    /**
     * Validador de topes de descuento permitidos según la política de la terminal POS.
     * Evalúa por separado el tope de descuento por ítem y el de descuento global — dos
     * campos y dos validaciones independientes desde 11.2.5, porque un solo tope
     * compartido no puede distinguir "el cajero puso descuento en esta línea" de
     * "esta línea recibió una porción del descuento global repartido".
     *
     * Señal confiable: `item['discount_percentage']` es SIEMPRE el descuento propio del
     * ítem, nunca tocado por el reparto del descuento global (que solo suma sobre
     * `discount_amount`, ver pos-workspace.blade.php::recalculateTotals() y su Regla de
     * Exclusión). Por eso el descuento por ítem se valida contra `discount_percentage`
     * y el descuento global se reconstruye como la diferencia entre `discount_total`
     * (declarado) y la suma de descuentos por ítem ya validados — nunca leyendo
     * `discount_amount` directo, que mezcla ambos orígenes.
     *
     * Sin `$context` (venta de backoffice, no atada a una terminal física) no hay a qué
     * política aplicar tras eliminar el global de `pos_settings` — se salta la validación
     * por completo, decisión explícita: el backoffice es de uso exclusivo de admins de
     * confianza. Ver 11.2 en docs/features/POS-Interfaz.md.
     */
    private function validateDiscounts(array $data, ?PosContext $context): void
    {
        if (! $context) {
            return;
        }

        $terminal = PosTerminal::find($context->terminal_id);
        if (! $terminal) {
            return;
        }

        $maxItem = $terminal->max_item_discount_percentage;
        $maxGlobal = $terminal->max_global_discount_percentage;

        // 1. Control de Descuento por Ítem (Línea por Línea) — usa discount_percentage,
        // la única señal que el reparto del descuento global nunca contamina.
        $grossTotal = 0;
        $itemDiscountTotal = 0;

        foreach ($data['items'] ?? [] as $item) {
            $pct = $item['discount_percentage'] ?? 0;
            $lineGross = $item['quantity'] * $item['price'];
            $grossTotal += $lineGross;

            if ($pct > 0) {
                if (! $terminal->allow_item_discount) {
                    throw new Exception('Los descuentos por ítem no están habilitados para esta terminal.');
                }

                if ($pct > $maxItem) {
                    throw new Exception("Un descuento por ítem ({$pct}%) supera el límite permitido para esta terminal ({$maxItem}%).");
                }
            }

            $itemDiscountTotal += ($lineGross * $pct) / 100;
        }

        // 2. Control de Descuento Global (Toda la Factura) — el monto global es lo que
        // sobra de discount_total una vez descontada la parte que ya se explicó por ítem.
        $globalDiscountAmount = max(0, ($data['discount_total'] ?? 0) - $itemDiscountTotal);

        if ($globalDiscountAmount > 0.01) {
            if (! $terminal->allow_global_discount) {
                throw new Exception('Los descuentos globales no están habilitados para esta terminal.');
            }

            // Bajo la Regla de Exclusión, el global solo se reparte entre ítems sin
            // descuento propio — el remanente relevante para el % es justamente
            // gross - itemDiscountTotal, igual que en pos-workspace.blade.php.
            $remaining = $grossTotal - $itemDiscountTotal;
            if ($remaining > 0) {
                $globalPct = ($globalDiscountAmount / $remaining) * 100;
                if ($globalPct > $maxGlobal) {
                    throw new Exception("El descuento global ({$globalPct}%) supera el límite permitido para esta terminal ({$maxGlobal}%).");
                }
            }
        }
    }

    /**
     * Valida la existencia, vigencia y disponibilidad numérica de los rangos de NCF dominicanos.
     */
    private function validateNcfSequence($ncfTypeId)
    {
        $exists = NcfSequence::where('ncf_type_id', $ncfTypeId)
            ->where('status', NcfSequence::STATUS_ACTIVE)
            ->where('expiry_date', '>=', now()) // Control de vigencia fiscal cronológica
            ->whereColumn('current', '<', 'to')   // Asegura que queden números disponibles en el rango aprobado
            ->exists();
        if (! $exists) {
            throw new Exception('Error Fiscal: Secuencias agotadas o vencidas.');
        }
    }

    /**
     * Orquesta el proceso inverso y atómico de la venta (Anulación completa).
     * Devuelve mercancía al stock, cancela CxC (si no tienen cobros previos), invalida NCF y contabilidad.
     *
     * * @param Sale $sale Instancia de la venta a anular.
     * @param  string|null  $reason  Motivo justificado de la cancelación.
     */
    public function cancel(Sale $sale, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($sale, $reason) {
            if ($sale->status === Sale::STATUS_CANCELED) {
                throw new Exception('La venta ya se encuentra anulada.');
            }

            // Regla de Negocio Crítica: Si la factura fue a crédito, no se puede anular si el cliente ya
            // realizó abonos parciales o totales a esa cuenta por cobrar (integridad de caja).
            $receivables = Receivable::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->get();

            foreach ($receivables as $receivable) {
                if ($receivable->current_balance < $receivable->total_amount || $receivable->status === Receivable::STATUS_PAID) {
                    throw new Exception('No se puede anular: Una de las cuentas por cobrar vinculadas ya tiene abonos.');
                }
                $this->receivableService->cancelReceivable($receivable);
            }

            // Invalida el log fiscal cambiando el estado a anulado ("VOIDED") para fines de reportes de la DGII (v.g. Formato 607)
            NcfLog::where('sale_id', $sale->id)
                ->update([
                    'status' => NcfLog::STATUS_VOIDED,
                    'cancellation_reason' => $reason ?? 'Anulación de venta manual',
                ]);

            // Revierte de forma contable el asiento de diario original mediante un contra-asiento de diario automático.
            $this->generateCancellationAccountingEntry($sale);

            // Reingreso físico de mercancía al stock.
            // NOTA ARQUITECTÓNICA: Se usa TYPE_ADJUSTMENT en lugar de TYPE_INPUT para no inflar artificialmente las
            // métricas de compras/entradas ordinarias en los reportes analíticos de inventario.
            foreach ($sale->items as $item) {
                $this->inventoryService->register([
                    'warehouse_id' => $sale->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'type' => InventoryMovement::TYPE_ADJUSTMENT,
                    'description' => "Reversión de costo por anulación {$sale->number}",
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                ]);
            }

            // Cancela el estado de la entidad Invoice vinculada de forma interna.
            $this->invoiceService->cancelInvoice($sale);

            return $sale->update(['status' => Sale::STATUS_CANCELED]);
        });
    }

    /**
     * Localiza el asiento original publicado de la venta y ejecuta un proceso contable inverso (Reversión).
     */
    protected function generateCancellationAccountingEntry(Sale $sale)
    {
        $originalEntry = JournalEntry::where('reference', $sale->number)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($originalEntry) {
            // El JournalService se encarga de crear el asiento espejo con los débitos/créditos invertidos
            // manteniendo intacto el histórico de auditoría del sistema de doble entrada.
            $this->journalService->reverse($originalEntry->id, [
                'entry_date' => now(),
                'description' => "Reversión por anulación de venta {$sale->number}",
            ]);
        }
    }
}
