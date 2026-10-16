<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreFinancialMovementRequest;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Services\Accounting\Treasury\FinancialAccountService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Movimientos de Tesorería (v1.5.0 REQ-3.2 y REQ-3.5): el kardex de todas las cuentas
 * (App\Livewire\App\Finance\FinancialMovementTable), la vista "Nuevo movimiento" y
 * anular o corregir un movimiento manual desde su detalle.
 */
class FinancialMovementController extends Controller
{
    public function __construct(protected FinancialAccountService $service) {}

    /** ?account= abre el listado filtrado por esa cuenta ("Ver todos" del show). */
    public function index(Request $request)
    {
        return view('finance.treasury.movements.index', ['accountId' => $request->integer('account') ?: null]);
    }

    /** ?account= la trae elegida desde el show. */
    public function create(Request $request)
    {
        return view('finance.treasury.movements.create', [
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name', 'type', 'current_balance']),
            'types' => FinancialAccountService::getMoveTypes(),
            'selected' => $request->integer('account') ?: null,
        ]);
    }

    public function store(StoreFinancialMovementRequest $request)
    {
        $account = FinancialAccount::findOrFail($request->validated('financial_account_id'));
        $to = $request->validated('to_account_id') ? FinancialAccount::findOrFail($request->validated('to_account_id')) : null;
        $type = $request->validated('type');

        try {
            $this->service->move($type, $account, (float) $request->validated('amount'),
                $request->validated('movement_date'), $request->validated('notes'), $to);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('finance.treasury.show', $account)->with('success', match ($type) {
            FinancialAccountService::MOVE_TRANSFER => "Transferencia a \"{$to->name}\" registrada.",
            FinancialAccountService::MOVE_WITHDRAWAL => 'Retiro registrado.',
            default => 'Depósito registrado.',
        });
    }

    public function void(Request $request, FinancialMovement $movement)
    {
        $data = $request->validate(['void_reason' => 'required|string|min:5|max:255'], [], ['void_reason' => 'motivo']);

        try {
            $this->service->void($movement, $data['void_reason']);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['void_reason' => $e->getMessage()]);
        }

        return back()->with('success', "{$movement->origin_label} anulado: se registró su reverso.");
    }

    public function correct(Request $request, FinancialMovement $movement)
    {
        $data = $request->validate([
            'correct_amount' => 'required|numeric|gt:0|max:9999999999.99',
            'correct_reason' => 'required|string|min:5|max:255',
        ], [], ['correct_amount' => 'monto correcto', 'correct_reason' => 'motivo']);

        try {
            $this->service->correct($movement, (float) $data['correct_amount'], $data['correct_reason']);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['correct_amount' => $e->getMessage()]);
        }

        return back()->with('success', "{$movement->origin_label} corregido a ".number_format((float) $data['correct_amount'], 2).'.');
    }
}
