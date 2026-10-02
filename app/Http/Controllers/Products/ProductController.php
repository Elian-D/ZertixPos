<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Inventory\InventoryMovement;
use App\Models\Products\Product;
use App\Services\Products\ProductCatalogService;
use App\Services\Products\ProductService;
use App\Traits\SoftDeletesTrait;

class ProductController extends Controller
{
    use SoftDeletesTrait;

    /** Movimientos en la pestaña de historial del show (el resto, en su listado). */
    private const TAB_LIMIT = 25;

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\ProductTable.
     */
    public function index()
    {
        return view('products.index');
    }

    /**
     * Ficha del producto/servicio (v1.4.0 Fase 3, patrón Infolist con pestañas —
     * /filament-show). Reemplaza el modal "view-product" del listado.
     */
    public function show(Product $product)
    {
        $product->load([
            'category:id,name',
            'unit:id,name,abbreviation',
            'productTaxes',
            'stocks' => fn ($q) => $q->with('warehouse:id,name,type,is_active')->orderByDesc('quantity'),
        ]);

        $totalStock = (float) $product->stocks->sum('quantity');
        $totalMin = (float) $product->stocks->sum('min_stock');

        // Márgenes sobre el precio neto (sin impuesto): margen = ganancia / precio,
        // markup = ganancia / costo. Sin precio o sin costo, no hay porcentaje.
        $price = (float) $product->price;
        $cost = (float) $product->cost;
        $profit = $price - $cost;

        $showMovements = $product->isProduct() && auth()->user()->can('inventory_movements.view');

        return view('products.show', [
            'product' => $product,
            'totalStock' => $totalStock,
            'totalMin' => $totalMin,
            'condition' => $product->stockCondition($totalStock, $totalMin),
            'profit' => $profit,
            'margin' => $price > 0 ? $profit / $price * 100 : null,
            'markup' => $cost > 0 ? $profit / $cost * 100 : null,
            'tabLimit' => self::TAB_LIMIT,
            'movements' => $showMovements
                ? InventoryMovement::where('product_id', $product->id)
                    ->with(['warehouse:id,name', 'toWarehouse:id,name', 'user:id,name'])
                    ->latest()
                    ->limit(self::TAB_LIMIT)
                    ->get()
                : null,
            'movementsCount' => $showMovements ? InventoryMovement::where('product_id', $product->id)->count() : 0,
        ]);
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
