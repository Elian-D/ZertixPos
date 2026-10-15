<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreExpenseRequest;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Purchases\Vendor;
use App\Services\Accounting\Payables\ExpenseService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Gastos directos (v1.5.0 REQ-4.4). El listado vive en App\Livewire\App\Finance\ExpenseTable;
 * aquí registrar, el show y anular.
 */
class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $service) {}

    public function index()
    {
        return view('finance.expenses.index');
    }

    /** ?vendor= lo trae elegido desde el show del proveedor. */
    public function create(Request $request)
    {
        return view('finance.expenses.create', [
            'selectedVendor' => $request->integer('vendor') ?: null,
            'categories' => Expense::getCategories(),
            'categoryHints' => Expense::getCategoryHints(),
            'conditions' => Expense::getConditions(),
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name', 'type', 'current_balance']),
            // Sin el módulo de compras no hay proveedores: el gasto va sin proveedor.
            'vendors' => module_enabled('purchases')
                ? Vendor::where('is_active', true)->orderBy('business_name')
                    ->get(['id', 'business_name', 'trade_name', 'payment_condition', 'credit_days', 'default_financial_account_id'])
                : collect(),
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        try {
            $expense = $this->service->create($request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('finance.expenses.show', $expense)->with('success', $expense->isCash()
            ? "Gasto {$expense->number} registrado: el dinero salió de la cuenta."
            : "Gasto {$expense->number} registrado: nació la cuenta por pagar {$expense->payable->number}.");
    }

    public function show(Expense $expense)
    {
        $expense->load([
            'vendor', 'account:id,name', 'creator:id,name', 'voider:id,name',
            'payable.payments' => fn ($q) => $q->with('account:id,name')->latest('id'),
        ]);

        return view('finance.expenses.show', ['expense' => $expense]);
    }

    public function void(Request $request, Expense $expense)
    {
        $data = $request->validate(['void_reason' => 'required|string|min:5|max:255'], [], ['void_reason' => 'motivo']);

        try {
            $this->service->void($expense, $data['void_reason']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('finance.expenses.show', $expense)->with('success', $expense->isCash()
            ? 'Gasto anulado: el dinero volvió a la cuenta.'
            : 'Gasto anulado junto con su cuenta por pagar y sus pagos.');
    }
}
