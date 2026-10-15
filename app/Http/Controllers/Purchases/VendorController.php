<?php

namespace App\Http\Controllers\Purchases;

use App\Enums\TaxIdentifierType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\SaveVendorRequest;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use App\Models\Purchases\Vendor;
use App\Services\Purchases\VendorService;

/**
 * Proveedores (v1.5.0 REQ-4.1). El listado vive en App\Livewire\App\Purchases\VendorTable
 * (con la papelera como pestaña, restaurar y borrado definitivo); aquí el formulario, el
 * show y enviar a la papelera.
 */
class VendorController extends Controller
{
    /** Documentos recientes en cada pestaña del show. */
    const SHOW_LIMIT = 30;

    public function __construct(protected VendorService $service) {}

    public function index()
    {
        return view('purchases.vendors.index');
    }

    public function create()
    {
        return view('purchases.vendors.create', $this->formData());
    }

    public function store(SaveVendorRequest $request)
    {
        $vendor = $this->service->create($request->validated());

        return redirect()->route('purchases.vendors.show', $vendor)
            ->with('success', "Proveedor \"{$vendor->display_name}\" registrado.");
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('defaultAccount:id,name');

        $payables = $vendor->payables()->with(['source', 'payments' => fn ($q) => $q->with('account:id,name')->latest('id')])
            ->latest('id')->limit(self::SHOW_LIMIT)->get();
        $expenses = $vendor->expenses()->with('account:id,name')->latest('expense_date')->latest('id')->limit(self::SHOW_LIMIT)->get();

        return view('purchases.vendors.show', [
            'vendor' => $vendor,
            'payables' => $payables,
            'expenses' => $expenses,
            'summary' => [
                'pending' => (float) $vendor->payables()->open()->sum('balance'),
                'overdue' => $vendor->payables()->open()->whereDate('due_date', '<', today())->count(),
                // Lo que se le ha comprado o gastado con él: gastos y CxP no anulados.
                'total' => (float) $vendor->expenses()->where('status', Expense::STATUS_APPLIED)->sum('amount'),
                'payables_count' => $vendor->payables()->where('status', '!=', Payable::STATUS_VOIDED)->count(),
                'expenses_count' => $vendor->expenses()->count(),
            ],
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return view('purchases.vendors.edit', $this->formData() + ['vendor' => $vendor]);
    }

    public function update(SaveVendorRequest $request, Vendor $vendor)
    {
        $this->service->update($vendor, $request->validated());

        return redirect()->route('purchases.vendors.show', $vendor)->with('success', 'Datos del proveedor actualizados.');
    }

    /** A la papelera: conserva su historial; se restaura desde el listado. */
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('purchases.vendors.index')
            ->with('success', "Proveedor \"{$vendor->display_name}\" enviado a la papelera.");
    }

    private function formData(): array
    {
        return [
            'taxTypes' => collect(TaxIdentifierType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(),
            'conditions' => Vendor::getConditions(),
            'accounts' => FinancialAccount::active()->orderBy('name')->get(['id', 'name', 'type']),
        ];
    }
}
