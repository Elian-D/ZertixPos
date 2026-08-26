<?php

namespace App\Http\Controllers\Products;

use App\Filters\Products\ProductsFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\BulkProductRequest; // Para la papelera
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Products\Product;
use App\Services\Products\ProductCatalogService;
use App\Services\Products\ProductService;
use App\Tables\ProductTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use SoftDeletesTrait;

    /**
     * Listado principal con Pipeline de Filtros y AJAX
     */
    public function index(Request $request, ProductCatalogService $catalogService)
    {
        // 1. Configuración de columnas visibles y paginación
        $visibleColumns = $request->input('columns', ProductTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // 2. Aplicación de filtros mediante el Pipeline y Eager Loading
        $products = (new ProductsFilters($request))
            ->apply(Product::query()->withIndexRelations())
            ->paginate($perPage)
            ->withQueryString();

        // 3. Respuesta para peticiones AJAX (DataTable)
        if ($request->ajax()) {
            return view('products.partials.table', [
                'products' => $products,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ProductTable::allColumns(),
                'defaultDesktop' => ProductTable::defaultDesktop(),
                'defaultMobile' => ProductTable::defaultMobile(),
                'bulkActions' => true,
            ])->render();
        }

        // 4. Carga de la vista completa
        return view('products.index', array_merge(
            [
                'products' => $products,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ProductTable::allColumns(),
                'defaultDesktop' => ProductTable::defaultDesktop(),
                'defaultMobile' => ProductTable::defaultMobile(),
                'bulkActions' => true,
            ],
            $catalogService->getForFilters() // Trae categories y units activos
        ));
    }

    /**
     * Acciones masivas (Eliminar, Activar, Cambiar Categoría)
     */
    public function bulk(BulkProductRequest $request, ProductService $productService)
    {
        try {
            $count = $productService->performBulkAction(
                $request->ids,
                $request->action,
                $request->value
            );

            $label = $productService->getActionLabel($request->action);
            $message = "Se ha {$label} correctamente {$count} productos.";

            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en acción masiva de productos: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la operación masiva.',
            ], 422);
        }
    }

    public function create(ProductCatalogService $catalogService)
    {
        return view('products.create', $catalogService->getForForm());
    }

    public function store(StoreProductRequest $request, ProductService $productService)
    {
        // Enviamos los datos validados y el archivo de imagen por separado
        $product = $productService->createProduct(
            $request->validated(),
            $request->file('image')
        );

        return redirect()->route('inventory.products.index')
            ->with('success', "Producto {$product->name} ({$product->sku}) creado correctamente.");
    }

    public function edit(Product $product, ProductCatalogService $catalogService)
    {
        return view('products.edit', array_merge(
            ['product' => $product],
            $catalogService->getForForm()
        ));
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $productService)
    {
        $productService->updateProduct(
            $product,
            $request->validated(),
            $request->file('image')
        );

        return redirect()->route('inventory.products.index')
            ->with('success', "Producto {$product->name} actualizado correctamente.");
    }

    public function destroy(Product $product)
    {
        return $this->destroyTrait($product, null);
    }

    /* Configuración del Trait para la papelera */
    protected function getModelClass(): string
    {
        return Product::class;
    }

    protected function getViewFolder(): string
    {
        return 'products';
    }

    protected function getRouteIndex(): string
    {
        return 'inventory.products.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'inventory.products.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Producto';
    }
}
