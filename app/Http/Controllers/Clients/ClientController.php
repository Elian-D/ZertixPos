<?php

namespace App\Http\Controllers\Clients;

use App\Exports\Clients\ClientsExport;
use App\Exports\Clients\ClientsTemplateExport;
use App\Filters\Client\ClientFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\BulkClientRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Imports\ClientsImport;
use App\Models\Clients\Client;
use App\Services\Client\ClientCatalogService;
use App\Services\Client\ClientService;
use App\Tables\ClientTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ClientController extends Controller
{
    use SoftDeletesTrait;

    public function index(Request $request, ClientCatalogService $catalogService)
    {
        // 1. Parámetros de UI
        $visibleColumns = $request->input('columns', ClientTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // 2. Ejecución del Pipeline de Filtros
        $clients = (new ClientFilters($request))
            ->apply(Client::query()->withIndexRelations())
            ->paginate($perPage)
            ->withQueryString();

        // 3. Respuesta AJAX (Solo la tabla)
        if ($request->ajax()) {
            return view('clients.partials.table', [
                'clients' => $clients,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ClientTable::allColumns(),
                'defaultDesktop' => ClientTable::defaultDesktop(),
                'defaultMobile' => ClientTable::defaultMobile(),
                'bulkActions' => true,
            ])->render();
        }

        // 4. Respuesta Vista Completa
        return view('clients.index', array_merge(
            [
                'clients' => $clients,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ClientTable::allColumns(),
                'defaultDesktop' => ClientTable::defaultDesktop(),
                'defaultMobile' => ClientTable::defaultMobile(),
                'bulkActions' => true,
            ],
            $catalogService->getForFilters() // Inyecta states, taxIdentifierTypes, etc.
        ));
    }

    /**
     * Acciones masivas
     */
    public function bulk(BulkClientRequest $request, ClientService $clientService)
    {
        try {
            $count = $clientService->performBulkAction(
                $request->ids,
                $request->action,
                $request->value
            );

            $label = $clientService->getActionLabel($request->action);
            $message = "Se han {$label} correctamente {$count} registros.";

            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en acción masiva de clientes: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la operación masiva.',
            ], 422);
        }
    }

    // Exportar clientes a Excel
    public function export(Request $request)
    {
        // 1. Aplicamos tus filtros existentes
        $query = (new ClientFilters($request))
            ->apply(Client::query()->withIndexRelations());

        // 2. IMPORTANTE: Ignoramos las columnas seleccionadas de la vista
        return Excel::download(
            new ClientsExport($query),
            'respaldo-clientes-'.now()->format('d-m-Y-h:ia').'.xlsx'
        );
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
        // El trait manejará la lógica de borrado suave y redirección
        return $this->destroyTrait($client, null);
    }

    /* ===========================
     |  CONFIGURACIÓN DEL TRAIT
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
