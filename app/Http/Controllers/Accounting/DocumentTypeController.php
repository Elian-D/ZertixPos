<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\DocumentTypes\UpdateDocumentTypeRequest;
use App\Models\Accounting\DocumentType;
use App\Services\Accounting\DocumentType\DocumentTypeService;

class DocumentTypeController extends Controller
{
    public function __construct(
        protected DocumentTypeService $service
    ) {}

    /**
     * Catálogo fijo (FAC, PAG/COB) — sin búsqueda, filtros ni paginación,
     * son 2 filas que el sistema sembró y sabe usar.
     */
    public function index()
    {
        $items = DocumentType::query()->withIndexRelations()->orderBy('name')->get();

        return view('accounting.document_types.index', compact('items'));
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

            return redirect()->route('configuration.document_types.index')
                ->with('success', 'Tipo de documento actualizado.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }
}
