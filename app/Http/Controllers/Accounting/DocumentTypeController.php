<?php

namespace App\Http\Controllers\Accounting;

use App\Filters\Accounting\DocumentTypeFilters\DocumentTypeFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\DocumentTypes\StoreDocumentTypeRequest;
use App\Http\Requests\Accounting\DocumentTypes\UpdateDocumentTypeRequest;
use App\Models\Accounting\DocumentType;
use App\Services\Accounting\DocumentType\DocumentTypeCatalogService;
use App\Services\Accounting\DocumentType\DocumentTypeService;
use App\Tables\AccountingTables\DocumentTypeTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected DocumentTypeService $service,
        protected DocumentTypeCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', DocumentTypeTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        $query = (new DocumentTypeFilters($request))
            ->apply(DocumentType::query()->withIndexRelations())
            ->latest();

        $items = $query->paginate($perPage)->withQueryString();
        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('accounting.document_types.partials.table', [
                'items' => $items,
                'visibleColumns' => $visibleColumns,
                'allColumns' => DocumentTypeTable::allColumns(),
                'defaultDesktop' => DocumentTypeTable::defaultDesktop(),
                'defaultMobile' => DocumentTypeTable::defaultMobile(),
            ])->render();
        }

        return view('accounting.document_types.index', array_merge(
            [
                'items' => $items,
                'visibleColumns' => $visibleColumns,
                'allColumns' => DocumentTypeTable::allColumns(),
                'defaultDesktop' => DocumentTypeTable::defaultDesktop(),
                'defaultMobile' => DocumentTypeTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    public function create()
    {
        return view('accounting.document_types.create');
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        try {
            $this->service->create($request->validated());

            return redirect()->route('accounting.document_types.index')
                ->with('success', 'Tipo de documento creado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al crear: '.$e->getMessage());
        }
    }

    public function edit(DocumentType $document_type)
    {
        return view('accounting.document_types.edit', [
            'item' => $document_type,
            'hasIssuedDocuments' => $document_type->hasIssuedDocuments(),
        ]);
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $document_type)
    {
        try {
            $this->service->update($document_type, $request->validated());

            return redirect()->route('accounting.document_types.index')
                ->with('success', 'Tipo de documento actualizado.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $type = DocumentType::findOrFail($id);

        // Validar si tiene documentos asociados antes de eliminar (opcional)
        return $this->destroyTrait($type);
    }

    // Configuración para SoftDeletesTrait
    protected function getModelClass(): string
    {
        return DocumentType::class;
    }

    protected function getViewFolder(): string
    {
        return 'accounting.document_types';
    }

    protected function getRouteIndex(): string
    {
        return 'accounting.document_types.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'accounting.document_types.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Tipo de Documento';
    }
}
