<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ReconcileFinancialAccountRequest;
use App\Http\Requests\Accounting\SaveFinancialAccountRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialReconciliation;
use App\Models\Sales\Pos\PosSetting;
use App\Models\Sales\Pos\PosTerminal;
use App\Services\Accounting\Treasury\CustomerPaymentService;
use App\Services\Accounting\Treasury\FinancialAccountService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Cajas y Bancos (v1.5.0 Fase 3): las cuentas, su formulario, su show de consulta y la
 * vista de conciliar. El listado son tarjetas por tipo (sin tabla Livewire: son pocas
 * cuentas); los movimientos, en FinancialMovementController.
 */
class FinancialAccountController extends Controller
{
    /** Movimientos recientes del show; el resto, en Movimientos de Tesorería. */
    const SHOW_MOVEMENTS = 30;

    public function __construct(protected FinancialAccountService $service) {}

    /** Tarjetas por tipo (REQ-3.5): un negocio tiene pocas cuentas, no amerita tabla. */
    public function index(CustomerPaymentService $customerPayments)
    {
        $accounts = FinancialAccount::withIndexRelations()->orderBy('name')->get();
        [$active, $inactive] = $accounts->partition(fn (FinancialAccount $a) => $a->is_active);
        [$banks, $cash] = $active->partition(fn (FinancialAccount $a) => $a->isBank());

        return view('finance.treasury.index', [
            'cash' => $cash->values(),
            'banks' => $banks->values(),
            'inactive' => $inactive->values(),
            'totals' => [
                'all' => (float) $active->sum('current_balance'),
                'cash' => (float) $cash->sum('current_balance'),
                'bank' => (float) $banks->sum('current_balance'),
            ],
            // Cuentas que reciben los pagos del POS: la por defecto y las de cada terminal.
            'assigned' => PosTerminal::whereNotNull('financial_account_id')->pluck('financial_account_id')
                ->push(PosSetting::query()->value('default_financial_account_id'))
                ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'missingBankAccount' => $customerPayments->missingDefaultAccount(),
        ]);
    }

    public function create()
    {
        return view('finance.treasury.create', ['types' => FinancialAccount::getTypes()]);
    }

    public function store(SaveFinancialAccountRequest $request)
    {
        $account = $this->service->create(
            $request->safe()->only(['name', 'type', 'bank_name', 'last_four_digits', 'notes']),
            (float) $request->validated('opening_balance'),
            $request->validated('opening_date'),
        );

        return redirect()->route('finance.treasury.show', $account)
            ->with('success', "Cuenta \"{$account->name}\" creada con su saldo de hoy.");
    }

    public function show(FinancialAccount $account)
    {
        $account->load(['reconciliations' => fn ($q) => $q->with('creator:id,name')->latest('id')->limit(10)]);

        return view('finance.treasury.show', [
            'account' => $account,
            'movements' => $account->movements()->withIndexRelations()
                ->latest('id')->limit(self::SHOW_MOVEMENTS)->get(),
            'movementsCount' => $account->movements()->count(),
            'assignedToPos' => $this->service->assignedToPos($account),
        ]);
    }

    public function edit(FinancialAccount $account)
    {
        return view('finance.treasury.edit', [
            'account' => $account,
            'types' => FinancialAccount::getTypes(),
            'assignedToPos' => $this->service->assignedToPos($account),
        ]);
    }

    public function update(SaveFinancialAccountRequest $request, FinancialAccount $account)
    {
        try {
            $this->service->update($account, $request->safe()->only(['name', 'type', 'bank_name', 'last_four_digits', 'notes']));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['type' => $e->getMessage()]);
        }

        return redirect()->route('finance.treasury.show', $account)->with('success', 'Datos de la cuenta actualizados.');
    }

    public function toggle(FinancialAccount $account)
    {
        try {
            $active = $this->service->toggleActive($account);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cuenta \"{$account->name}\" ".($active ? 'activada.' : 'desactivada.'));
    }

    /** Vista "Conciliar" (REQ-3.3); ?account= la trae elegida desde el show. */
    public function reconcileForm(Request $request)
    {
        $accounts = FinancialAccount::active()->with('lastReconciliation')->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return view('finance.treasury.reconcile', [
            'accounts' => $accounts,
            'selected' => $request->integer('account') ?: null,
            'reasons' => FinancialReconciliation::getReasons(),
        ]);
    }

    public function reconcile(ReconcileFinancialAccountRequest $request)
    {
        $account = FinancialAccount::findOrFail($request->validated('financial_account_id'));

        try {
            $reconciliation = $this->service->reconcile(
                $account,
                (float) $request->validated('real_balance'),
                $request->validated('reconciled_at'),
                $request->validated('reason'),
                $request->validated('notes'),
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['reason' => $e->getMessage()]);
        }

        return redirect()->route('finance.treasury.show', $account)->with('success', $reconciliation->hasDifference()
            ? 'Cuenta conciliada: se ajustó la diferencia de '.number_format((float) $reconciliation->difference, 2).'.'
            : 'Cuenta conciliada: cuadró sin diferencia.');
    }
}
