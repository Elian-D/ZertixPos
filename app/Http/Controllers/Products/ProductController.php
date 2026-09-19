<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Products\Product;
use App\Services\Products\ProductCatalogService;
use App\Services\Products\ProductService;
use App\Traits\SoftDeletesTrait;

class ProductController extends Controller
{
    use SoftDeletesTrait;

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\ProductTable.
     */
    public function index()
    {
        return view('products.index');
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

    /* Configuración del Trait para destroy() (eliminados/restaurar/borrarDefinitivo
     * del trait ya no se usan — reemplazados por el tab "Papelera" + ProductTable
     * ::restore()/forceDelete(), ver docs/analisis/politica-soft-deletes.md §6). */
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
