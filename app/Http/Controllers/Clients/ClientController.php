<?php

namespace App\Http\Controllers\Clients;

use App\Exports\Clients\ClientsTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Imports\ClientsImport;
use App\Models\Clients\Client;
use App\Services\Client\ClientCatalogService;
use App\Services\Client\ClientService;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ClientController extends Controller
{
    use SoftDeletesTrait;

    /** Registros por pestaña en el show del cliente (el resto, en su listado). */
    private const TAB_LIMIT = 25;

    public function index()
    {
        // REQ-0.7: la tabla vive ahora en App\Livewire\App\Clients\ClientTable
        // (motor Livewire, Fase 0) — este método solo renderiza el layout.
        return view('clients.index');
    }

    /**
     * Detalle del cliente (v1.4.0 Fase 3, estilo Infolist — ver /filament-show).
     * Reemplaza el modal "view-client" del listado.
     */
    public function show(Client $client)
    {
        $client->load(['provincia:id,name', 'municipio:id,name', 'accountingAccount:id,code,name']);

        // Pestañas: los últimos TAB_LIMIT registros + el conteo total. Cada pestaña
        // solo se consulta si el usuario puede verla.
        $showQuotes = module_enabled('sales.quotes') && auth()->user()->can('quotes.view');
        $showInvoices = auth()->user()->can('invoices.view');

        return view('clients.show', [
            'client' => $client,
            'isMoroso' => $client->esMoroso(),
            'tabLimit' => self::TAB_LIMIT,
            'quotes' => $showQuotes ? $client->quotes()->latest()->limit(self::TAB_LIMIT)->get() : null,
            'quotesCount' => $showQuotes ? $client->quotes()->count() : 0,
            'invoices' => $showInvoices
                ? $client->invoices()
                    ->with(['sale:id,number,sale_date,payment_type,net_amount,tax_amount', 'sale.receivable'])
                    ->latest('invoices.created_at')
                    ->limit(self::TAB_LIMIT)
                    ->get()
                : null,
            'invoicesCount' => $showInvoices ? $client->invoices()->count() : 0,
        ]);
    }

    /**
     * Muestra la vista de importación
     */
    public function showImportForm()
    {
        return view('clients.import');
    }

    /**
     * Descarga la plantilla base de clientes
     */
    public function downloadTemplate()
    {
        // La Facade Excel se llama de forma estática correctamente
        return Excel::download(new ClientsTemplateExport, 'plantilla-importacion-clientes.xlsx');
    }

    /**
     * Procesa la importación
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Aumenté a 10MB
        ]);

        try {
            // Desactivar logs temporalmente para máximo rendimiento
            DB::connection()->disableQueryLog();

            Excel::import(new ClientsImport, $request->file('file'));

            return redirect()
                ->route('clients.index')
                ->with('success', 'Importación completada exitosamente.');

        } catch (ValidationException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Error en la importación: '.$e->getMessage()]);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(ClientCatalogService $catalogService)
    {

        return view('clients.create', $catalogService->getForForm());
    }

    /**
     * Almacenar nuevo cliente
     */
    public function store(StoreClientRequest $request, ClientService $clientService)
    {
        // El Request ya validó que el tax_identifier_type sea correcto
        $client = $clientService->createClient($request->validated());

        return redirect()
            ->route('clients.index')
            ->with('success', "Cliente {$client->name} creado correctamente.");
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Client $client, ClientCatalogService $catalogService)
    {

        return view('clients.edit', array_merge(
            ['client' => $client],
            $catalogService->getForForm() // Reutiliza la misma lógica de estados, países e IDs
        ));
    }

    /**
     * Actualizar cliente
     */
    public function update(UpdateClientRequest $request, Client $client, ClientService $clientService)
    {
        // El Request ya autorizó y validó los datos
        $clientService->updateClient($client, $request->validated());

        return redirect()
            ->route('clients.index')
            ->with('success', "Cliente {$client->name} actualizado correctamente.");
    }

    /**
     * Eliminar (Soft Delete)
     */
    public function destroy(Client $client)
    {
        abort_if($client->isConsumidorFinal(), 403, 'El Consumidor Final no se puede eliminar.');

        // El trait manejará la lógica de borrado suave y redirección
        return $this->destroyTrait($client, null);
    }

    /* ===========================
     |  CONFIGURACIÓN DEL TRAIT
     |  Solo destroyTrait() (usado por destroy() arriba) sigue alcanzable
     |  por HTTP. eliminadas()/restaurar()/borrarDefinitivo() del trait ya
     |  no tienen ruta — el tab "Papelera" del ClientTable Livewire las
     |  reemplazó (docs/analisis/politica-soft-deletes.md §6). Quedan como
     |  boilerplate inalcanzable, igual que SaleController con SoftDeletesTrait
     |  (ver mismo documento §4.2) — getRouteEliminadas() ya no resuelve a
     |  ninguna ruta real, pero nada la invoca.
     =========================== */
    protected function getModelClass(): string
    {
        return Client::class;
    }

    protected function getViewFolder(): string
    {
        return 'clients';
    }

    protected function getRouteIndex(): string
    {
        return 'clients.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'clients.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Cliente';
    }
}
