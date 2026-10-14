<?php

namespace App\Services\Accounting\Treasury;

use App\Models\Accounting\ClientCollection;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Models\Configuration\TipoPago;
use App\Models\Sales\Pos\PosSetting;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Sale;
use App\Models\Sales\SalePayment;
use Illuminate\Database\Eloquent\Model;

/**
 * Pagos de clientes que no son efectivo → cuenta de banco (v1.5.0 REQ-3.6).
 *
 * Cada pago con tarjeta, transferencia, depósito o cheque entra al momento a la cuenta de
 * banco de la terminal o, si no tiene, a la cuenta por defecto del POS (también la del
 * backoffice). Sin cuenta configurada (o inactiva) el pago no entra a ninguna cuenta y la
 * venta sigue igual. Las comisiones y diferencias del banco se arreglan conciliando.
 *
 * El efectivo se queda en la gaveta (llega al banco con un depósito manual); "Devolución"
 * (valor a favor) y el crédito no son dinero.
 */
class CustomerPaymentService
{
    /** Métodos que no entran a una cuenta de banco. */
    const NOT_BANK_METHODS = [TipoPago::EFECTIVO, TipoPago::DEVOLUCION, TipoPago::CREDITO];

    public function __construct(protected FinancialMovementService $movements) {}

    public function forSale(Sale $sale, ?int $terminalId): void
    {
        $account = $this->resolveAccount($terminalId);

        if (! $account) {
            return;
        }

        foreach ($sale->payments()->with('tipoPago:id,nombre,slug')->get() as $payment) {
            if (! $this->isBankMethod($payment->tipoPago) || (float) $payment->amount <= 0) {
                continue;
            }

            $this->register($account, $payment, (float) $payment->amount, $sale->sale_date->toDateString(),
                "Venta {$sale->number} · {$payment->tipoPago->nombre}");
        }
    }

    public function forCollection(ClientCollection $collection): void
    {
        $collection->loadMissing('tipoPago:id,nombre,slug');

        if (! $this->isBankMethod($collection->tipoPago)) {
            return;
        }

        $account = $this->resolveAccount($collection->pos_terminal_id);

        if ($account) {
            $this->register($account, $collection, (float) $collection->amount, $collection->payment_date->toDateString(),
                "Cobro {$collection->receipt_number} · {$collection->tipoPago->nombre}");
        }
    }

    /** Al anular la venta: reversa lo que entró al banco por sus pagos. */
    public function reverseSale(Sale $sale): void
    {
        $this->reverse(SalePayment::class, $sale->payments()->pluck('id')->all(), "Anulación de la venta {$sale->number}");
    }

    public function reverseCollection(ClientCollection $collection): void
    {
        $this->reverse(ClientCollection::class, [$collection->id], "Anulación del cobro {$collection->receipt_number}");
    }

    /** Cuenta de la terminal; si no tiene (o no sirve), la cuenta por defecto del POS. */
    public function resolveAccount(?int $terminalId): ?FinancialAccount
    {
        $ids = array_filter([
            $terminalId ? PosTerminal::whereKey($terminalId)->value('financial_account_id') : null,
            // Directo y no getSettings(): ese memo estático no ve un cambio hecho en el mismo proceso.
            PosSetting::query()->value('default_financial_account_id'),
        ]);

        foreach ($ids as $id) {
            $account = FinancialAccount::whereKey($id)->where('type', FinancialAccount::TYPE_BANK)->where('is_active', true)->first();
            if ($account) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Hay métodos de pago activos que no son efectivo (tarjeta, transferencia...) y no hay
     * una cuenta de banco por defecto válida: esos pagos no entran a ninguna cuenta. Lo usan
     * el aviso de Cajas y Bancos / configuración del POS y el paso del onboarding.
     */
    public function missingDefaultAccount(): bool
    {
        $hasBankMethods = TipoPago::seleccionable()->whereNotIn('slug', self::NOT_BANK_METHODS)->exists();

        return $hasBankMethods && $this->resolveAccount(null) === null;
    }

    private function isBankMethod(?TipoPago $tipo): bool
    {
        return $tipo !== null && ! in_array($tipo->slug, self::NOT_BANK_METHODS, true);
    }

    private function register(FinancialAccount $account, Model $reference, float $amount, string $date, string $notes): void
    {
        $this->movements->register(
            $account,
            FinancialMovement::DIRECTION_IN,
            $amount,
            FinancialMovement::ORIGIN_CUSTOMER_PAYMENT,
            $date,
            ['reference_type' => $reference::class, 'reference_id' => $reference->getKey(), 'notes' => $notes],
            fromDocument: true,
        );
    }

    private function reverse(string $type, array $ids, string $reason): void
    {
        if (! $ids) {
            return;
        }

        FinancialMovement::where('origin', FinancialMovement::ORIGIN_CUSTOMER_PAYMENT)
            ->where('reference_type', $type)
            ->whereIn('reference_id', $ids)
            ->whereNull('reversed_by_id')
            ->get()
            ->each(fn (FinancialMovement $m) => $this->movements->reverse($m, $reason, fromDocument: true));
    }
}
