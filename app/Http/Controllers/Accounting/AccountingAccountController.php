<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\AccountingAccount\StoreAccountingAccountRequest;
use App\Http\Requests\Accounting\AccountingAccount\UpdateAccountingAccountRequest;
use App\Models\Accounting\AccountingAccount;
use App\Services\Accounting\AccountingAccount\AccountCatalogService;
use App\Services\Accounting\AccountingAccount\AccountingAccountService;
use App\Tables\AccountingTables\AccountingAccountTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;

class AccountingAccountController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected AccountingAccountService $service,
        protected AccountCatalogService $catalogService
    ) {}

    /**
     * Vista principal del catálogo
     */
    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', AccountingAccountTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        // Obtenemos las cuentas con sus relaciones (Padre)
        // No usamos Pipeline de filtros por tu sugerencia, pero dejamos el query ordenado por código
        $accounts = AccountingAccount::with(['parent'])
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForForm();

        if ($request->ajax()) {
            return view('accounting.accounts.partials.table', [
                'items' => $accounts,
                'visibleColumns' => $visibleColumns,
                'allColumns' => AccountingAccountTable::allColumns(),
                'defaultDesktop' => AccountingAccountTable::defaultDesktop(),
                'defaultMobile' => AccountingAccountTable::defaultMobile(),
            ], $catalogs
            )->render();
        }

        return view('accounting.accounts.index', array_merge(
            [
                'items' => $accounts,
                'visibleColumns' => $visibleColumns,
                'allColumns' => AccountingAccountTable::allColumns(),
                'defaultDesktop' => AccountingAccountTable::defaultDesktop(),
                'defaultMobile' => AccountingAccountTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    /**
     * Almacenar nueva cuenta
     */
    public function store(StoreAccountingAccountRequest $request)
    {
        $account = $this->service->createAccount($request->validated());

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', "Cuenta \"{$account->code} - {$account->name}\" creada correctamente.");
    }

    /**
     * Actualizar cuenta existente
     */
    public function update(UpdateAccountingAccountRequest $request, AccountingAccount $accounting_account)
    {
        $this->service->updateAccount($accounting_account, $request->validated());

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', "Cuenta \"{$accounting_account->name}\" actualizada con éxito.");
    }

    /**
     * Eliminación lógica
     */
    public function destroy($id)
    {
        $account = AccountingAccount::findOrFail($id);

        // Validación: No permitir borrar si tiene sub-cuentas activas
        if ($account->children()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar la cuenta porque tiene sub-cuentas asociadas.');
        }

        return $this->destroyTrait($account);
    }

    /**
     * Configuración para el SoftDeletesTrait
     */
    protected function getModelClass(): string
    {
        return AccountingAccount::class;
    }

    protected function getViewFolder(): string
    {
        return 'accounting.accounts';
    }

    protected function getRouteIndex(): string
    {
        return 'finance.accounts.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'finance.accounts.eliminados';
    } // O una vista de papelera si decides crearla

    protected function getEntityName(): string
    {
        return 'Cuenta Contable';
    }
}
