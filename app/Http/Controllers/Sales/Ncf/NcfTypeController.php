<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Ncf\StoreNcfTypeRequest;
use App\Http\Requests\Sales\Ncf\UpdateNcfTypeRequest;
use App\Models\Sales\Ncf\NcfType;
use App\Services\Sales\Ncf\NcfCatalogService;
use App\Tables\SalesTables\Ncf\NcfTypeTable;
use Illuminate\Http\Request;

class NcfTypeController extends Controller
{
    public function __construct(
        protected NcfCatalogService $catalog
    ) {}

    /**
     * Lista todos los tipos de NCF.
     * Al ser una tabla maestra pequeña, no aplicamos filtros complejos ni paginación pesada.
     */
    public function index(Request $request)
    {
        // 1. Configuración de columnas visibles
        $visibleColumns = $request->input('columns', NcfTypeTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // 2. Obtener registros (Ordenados por código para fácil lectura: 01, 02, etc.)
        $types = NcfType::withCount('sequences')
            ->orderBy('code', 'asc')
            ->paginate($perPage);

        // 3. Preparar datos para la vista
        $data = [
            'items' => $types,
            'visibleColumns' => $visibleColumns,
            'allColumns' => NcfTypeTable::allColumns(),
            'defaultDesktop' => NcfTypeTable::defaultDesktop(),
            'defaultMobile' => NcfTypeTable::defaultMobile(),
            'boolean_options' => [1 => 'Sí', 0 => 'No'],
        ];

        // Retorno para AJAX (Carga de tabla) o Vista completa
        if ($request->ajax()) {
            return view('sales.ncf.types.partials.table', $data)->render();
        }

        return view('sales.ncf.types.index', $data);
    }

    /**
     * Crear un nuevo tipo de comprobante (ej. si la DGII lanza uno nuevo).
     */
    public function store(StoreNcfTypeRequest $request)
    {
        NcfType::create($request->validated());

        return redirect()->route('finance.ncf.types.index')
            ->with('success', 'Tipo de comprobante registrado exitosamente.');
    }

    /**
     * Actualizar configuración (Nombre, si requiere RNC o si está activo).
     */
    public function update(UpdateNcfTypeRequest $request, NcfType $ncfType)
    {
        $ncfType->update($request->validated());

        return redirect()->route('finance.ncf.types.index')
            ->with('success', "Configuración de {$ncfType->name} actualizada.");
    }
}
