<?php

namespace App\Services\Accounting\Treasury;

use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Models\Accounting\FinancialReconciliation;
use App\Models\Sales\Pos\PosSetting;
use App\Models\Sales\Pos\PosTerminal;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cajas y Bancos (v1.5.0 REQ-3.2 y REQ-3.3): crear la cuenta con su saldo inicial,
 * registrar un movimiento manual (depósito, retiro o transferencia), anularlo o corregir
 * su monto, y conciliar. Todo movimiento pasa por FinancialMovementService.
 */
class FinancialAccountService
{
    const MOVE_DEPOSIT = 'deposit';
    const MOVE_WITHDRAWAL = 'withdrawal';
    const MOVE_TRANSFER = 'transfer';

    public function __construct(protected FinancialMovementService $movements) {}

    public static function getMoveTypes(): array
    {
        return [
            self::MOVE_DEPOSIT => 'Depósito',
            self::MOVE_WITHDRAWAL => 'Retiro',
            self::MOVE_TRANSFER => 'Transferencia',
        ];
    }

    /**
     * El saldo de hoy entra como movimiento 'opening' (también si es 0, para que el
     * kardex empiece ahí): equivale a la primera conciliación.
     *
     * @param  array{name: string, type: string, bank_name?: ?string, last_four_digits?: ?string, notes?: ?string}  $data
     */
    public function create(array $data, float $openingBalance, string $openingDate): FinancialAccount
    {
        return DB::transaction(function () use ($data, $openingBalance, $openingDate) {
            $account = FinancialAccount::create($this->clean($data) + ['is_active' => true]);

            $this->movements->register($account, FinancialMovement::DIRECTION_IN, $openingBalance, FinancialMovement::ORIGIN_OPENING, $openingDate);

            return $account->refresh();
        });
    }

    /** Edita los datos, nunca el saldo: el saldo solo cambia con movimientos. */
    public function update(FinancialAccount $account, array $data): FinancialAccount
    {
        if ($account->type === FinancialAccount::TYPE_BANK && ($data['type'] ?? null) !== FinancialAccount::TYPE_BANK && $this->assignedToPos($account)) {
            throw new DomainException('Esta cuenta recibe los pagos del POS: quítala de la configuración del POS y de las terminales antes de cambiarla a Caja.');
        }

        $account->update($this->clean($data));

        return $account;
    }

    /**
     * Desactivar exige saldo 0 (una cuenta inactiva no recibe movimientos, y un saldo ahí
     * quedaría atrapado) y que no reciba los pagos del POS (REQ-3.6).
     */
    public function toggleActive(FinancialAccount $account): bool
    {
        return DB::transaction(function () use ($account) {
            $account = FinancialAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if ($account->is_active) {
                if (round((float) $account->current_balance, 2) != 0) {
                    throw new DomainException("La cuenta {$account->name} tiene saldo: transfiérelo o concílialo a 0 antes de desactivarla.");
                }

                if ($this->assignedToPos($account)) {
                    throw new DomainException("La cuenta {$account->name} recibe los pagos del POS: quítala de la configuración del POS y de las terminales antes de desactivarla.");
                }
            }

            $account->update(['is_active' => ! $account->is_active]);

            return $account->is_active;
        });
    }

    /**
     * Movimiento manual desde la vista "Nuevo movimiento".
     *
     * @return FinancialMovement el movimiento de la cuenta de origen
     */
    public function move(string $type, FinancialAccount $account, float $amount, string $date, ?string $notes, ?FinancialAccount $to = null): FinancialMovement
    {
        return match ($type) {
            self::MOVE_DEPOSIT => $this->movements->register($account, FinancialMovement::DIRECTION_IN, $amount, FinancialMovement::ORIGIN_DEPOSIT, $date, ['notes' => $notes]),
            self::MOVE_WITHDRAWAL => $this->movements->register($account, FinancialMovement::DIRECTION_OUT, $amount, FinancialMovement::ORIGIN_WITHDRAWAL, $date, ['notes' => $notes]),
            self::MOVE_TRANSFER => $this->transfer($account, $to ?? throw new DomainException('Elige la cuenta de destino.'), $amount, $date, $notes)[0],
            default => throw new DomainException('Tipo de movimiento no válido.'),
        };
    }

    /**
     * Un 'out' en el origen y un 'in' en el destino, enlazados por counterpart_id.
     * Bloquea las dos cuentas en orden de id para no cruzarse con otra transferencia al revés.
     *
     * @return array{0: FinancialMovement, 1: FinancialMovement} [salida, entrada]
     */
    public function transfer(FinancialAccount $from, FinancialAccount $to, float $amount, string $date, ?string $notes, array $extra = [], bool $fromDocument = false): array
    {
        if ($from->id === $to->id) {
            throw new DomainException('La cuenta de destino debe ser distinta a la de origen.');
        }

        return DB::transaction(function () use ($from, $to, $amount, $date, $notes, $extra, $fromDocument) {
            FinancialAccount::whereIn('id', [$from->id, $to->id])->orderBy('id')->lockForUpdate()->get();

            $out = $this->movements->register($from, FinancialMovement::DIRECTION_OUT, $amount, FinancialMovement::ORIGIN_TRANSFER, $date, $extra + ['notes' => $notes], $fromDocument);
            $in = $this->movements->register($to, FinancialMovement::DIRECTION_IN, $amount, FinancialMovement::ORIGIN_TRANSFER, $date, $extra + [
                'notes' => $notes,
                'counterpart_id' => $out->id,
            ], $fromDocument);
            $out->forceFill(['counterpart_id' => $in->id])->save();

            return [$out, $in];
        });
    }

    /**
     * Anula un depósito, retiro o transferencia con su reverso, para un movimiento que no
     * debió existir. Una transferencia se anula entera: las dos patas, desde cualquiera de
     * las dos. Rechaza si una cuenta quedaría en negativo.
     */
    public function void(FinancialMovement $movement, string $reason): void
    {
        $this->assertEditable($movement);

        DB::transaction(function () use ($movement, $reason) {
            $this->movements->reverse($movement, $reason);

            if ($movement->origin === FinancialMovement::ORIGIN_TRANSFER && $movement->counterpart_id) {
                $this->movements->reverse($movement->counterpart, $reason);
            }
        });
    }

    /**
     * Corrige el monto de un depósito, retiro o transferencia mal escrito: en una
     * transacción anula el original y registra el correcto, que lo referencia. El saldo
     * no negativo se valida sobre el resultado final, no sobre el paso intermedio.
     *
     * @return FinancialMovement el movimiento correcto (en la cuenta del original)
     */
    public function correct(FinancialMovement $movement, float $amount, string $reason): FinancialMovement
    {
        $this->assertEditable($movement);

        if (round($amount, 2) == round((float) $movement->amount, 2)) {
            throw new DomainException('El monto correcto es igual al registrado.');
        }

        return DB::transaction(function () use ($movement, $amount, $reason) {
            $note = "Corrección: {$reason}";
            $ref = ['reference_type' => FinancialMovement::class, 'reference_id' => $movement->id];
            $date = $movement->movement_date->toDateString();

            // Pasos intermedios sin validar saldo: lo que importa es cómo quedan las cuentas.
            $this->movements->reverse($movement, $note, fromDocument: true);

            if ($movement->origin === FinancialMovement::ORIGIN_TRANSFER) {
                $counterpart = $movement->counterpart;
                $this->movements->reverse($counterpart, $note, fromDocument: true);

                [$from, $to] = $movement->isIn() ? [$counterpart->account, $movement->account] : [$movement->account, $counterpart->account];
                [$out, $in] = $this->transfer($from, $to, $amount, $date, $movement->notes, $ref, fromDocument: true);
                $new = $movement->isIn() ? $in : $out;
                $accounts = [$from, $to];
            } else {
                $new = $this->movements->register($movement->account, $movement->direction, $amount, $movement->origin, $date, $ref + ['notes' => $movement->notes], fromDocument: true);
                $accounts = [$movement->account];
            }

            foreach ($accounts as $account) {
                if (! $account->is_active) {
                    throw new DomainException("La cuenta {$account->name} está inactiva: actívala para corregir.");
                }
                $this->movements->assertAccountNotNegative($account);
            }

            return $new;
        });
    }

    /**
     * Sincera la cuenta contra la realidad: guarda la conciliación y, si hay diferencia,
     * un movimiento 'reconciliation' por ella, para que el saldo quede igual al real.
     */
    public function reconcile(FinancialAccount $account, float $realBalance, string $date, ?string $reason, ?string $notes): FinancialReconciliation
    {
        return DB::transaction(function () use ($account, $realBalance, $date, $reason, $notes) {
            $account = FinancialAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if (! $account->is_active) {
                throw new DomainException("La cuenta {$account->name} está inactiva: actívala para conciliarla.");
            }

            $system = round((float) $account->current_balance, 2);
            $real = round($realBalance, 2);
            $difference = round($real - $system, 2);

            if ($difference != 0 && ! $reason) {
                throw new DomainException('Elige el motivo de la diferencia.');
            }

            $reconciliation = FinancialReconciliation::create([
                'financial_account_id' => $account->id,
                'reconciled_at' => $date,
                'system_balance' => $system,
                'real_balance' => $real,
                'difference' => $difference,
                'reason' => $difference != 0 ? $reason : null,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            if ($difference != 0) {
                $this->movements->register(
                    $account,
                    $difference > 0 ? FinancialMovement::DIRECTION_IN : FinancialMovement::DIRECTION_OUT,
                    abs($difference),
                    FinancialMovement::ORIGIN_RECONCILIATION,
                    $date,
                    [
                        'reference_type' => FinancialReconciliation::class,
                        'reference_id' => $reconciliation->id,
                        'notes' => $reconciliation->reason_label.($notes ? ": {$notes}" : ''),
                    ],
                );
            }

            return $reconciliation;
        });
    }

    /** Si la cuenta recibe los pagos del POS: por defecto o de alguna terminal (REQ-3.6). */
    public function assignedToPos(FinancialAccount $account): bool
    {
        return (int) PosSetting::query()->value('default_financial_account_id') === $account->id
            || PosTerminal::where('financial_account_id', $account->id)->exists();
    }

    private function assertEditable(FinancialMovement $movement): void
    {
        if ($movement->isReversed()) {
            throw new DomainException('Este movimiento ya está anulado.');
        }

        if (! in_array($movement->origin, FinancialMovement::MANUAL_ORIGINS, true)) {
            throw new DomainException(match ($movement->origin) {
                FinancialMovement::ORIGIN_REVERSAL => 'Una anulación no se puede anular.',
                FinancialMovement::ORIGIN_OPENING, FinancialMovement::ORIGIN_RECONCILIATION => 'El saldo inicial y las conciliaciones no se anulan: si el saldo no cuadra, concilia de nuevo.',
                default => 'Este movimiento viene de otro documento y se anula desde ese documento.',
            });
        }
    }

    /** Los datos de banco solo aplican a una cuenta de banco. */
    private function clean(array $data): array
    {
        $isBank = ($data['type'] ?? null) === FinancialAccount::TYPE_BANK;

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'bank_name' => $isBank ? ($data['bank_name'] ?? null) : null,
            'last_four_digits' => $isBank ? ($data['last_four_digits'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
