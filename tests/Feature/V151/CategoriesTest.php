<?php

namespace Tests\Feature\V151;

use App\Livewire\App\Inventory\CategoryTable;
use App\Livewire\App\Inventory\ProductTable;
use App\Livewire\Sales\Pos\Pages\PosWorkspace;
use App\Models\Configuration\TipoPago;
use App\Models\Products\Category;
use App\Models\Sales\Sale;
use App\Services\Inventory\InventoryCountService;
use App\Services\Sales\SalesServices\SaleService;
use DomainException;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

/**
 * v1.5.1 Fase 2: categorías en dos niveles. La principal agrupa y la subcategoría recibe
 * los productos (PRD-00001 está en Hielo, subcategoría de "Agua y Hielo").
 */
class CategoriesTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPosWorkspace();
        $this->actingAs($this->cashier);
    }

    private function store(array $data)
    {
        return $this->post(route('configuration.categories.store'), $data + ['is_active' => '1']);
    }

    public function test_crear_principal_y_subcategoria_y_el_producto_solo_va_en_la_subcategoria(): void
    {
        $this->store(['kind' => 'parent', 'name' => 'ZZ Agua'])->assertSessionHasNoErrors();
        $agua = Category::where('name', 'ZZ Agua')->firstOrFail();

        $this->store(['kind' => 'child', 'name' => 'Botellones', 'parent_id' => $agua->id])->assertSessionHasNoErrors();
        $botellones = Category::where('name', 'Botellones')->firstOrFail();
        $this->assertSame($agua->id, $botellones->parent_id);

        $rule = ['category_id' => [Category::productRule()]];
        $this->assertTrue(Validator::make(['category_id' => $botellones->id], $rule)->passes());
        $this->assertTrue(Validator::make(['category_id' => $agua->id], $rule)->fails());
    }

    public function test_el_mismo_nombre_puede_existir_en_dos_principales_pero_no_dos_veces_en_la_misma(): void
    {
        $this->store(['kind' => 'parent', 'name' => 'Laptops']);
        $this->store(['kind' => 'parent', 'name' => 'Monitores']);
        $laptops = Category::where('name', 'Laptops')->value('id');
        $monitores = Category::where('name', 'Monitores')->value('id');

        $this->store(['kind' => 'child', 'name' => 'Dell', 'parent_id' => $laptops])->assertSessionHasNoErrors();
        $this->store(['kind' => 'child', 'name' => 'Dell', 'parent_id' => $monitores])->assertSessionHasNoErrors();
        $this->store(['kind' => 'child', 'name' => 'Dell', 'parent_id' => $laptops])->assertSessionHasErrors('name');

        $this->assertSame(2, Category::where('name', 'Dell')->count());
    }

    public function test_una_principal_con_subcategorias_o_una_subcategoria_con_productos_no_va_a_la_papelera(): void
    {
        $child = $this->product->category;
        $parent = $child->parent;

        $this->delete(route('configuration.categories.destroy', $parent))->assertSessionHas('error');
        $this->delete(route('configuration.categories.destroy', $child))->assertSessionHas('error');

        $this->assertFalse($parent->fresh()->trashed());
        $this->assertFalse($child->fresh()->trashed());
    }

    public function test_una_subcategoria_no_se_restaura_si_su_principal_esta_en_la_papelera(): void
    {
        $parent = Category::create(['name' => 'ZZ Principal']);
        $child = Category::create(['name' => 'ZZ Hija', 'parent_id' => $parent->id]);
        $child->delete();
        $parent->delete();

        Livewire::test(CategoryTable::class)
            ->call('restore', $child->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error');

        $this->assertTrue($child->fresh()->trashed());
    }

    public function test_una_principal_con_subcategorias_no_pasa_a_ser_subcategoria(): void
    {
        $parent = $this->product->category->parent;
        $other = Category::create(['name' => 'ZZ Otra']);

        $this->put(route('configuration.categories.update', $parent), [
            'kind' => 'child', 'name' => $parent->name, 'parent_id' => $other->id, 'is_active' => '1',
        ])->assertSessionHasErrors('kind');
    }

    public function test_filtrar_por_la_principal_trae_los_productos_de_sus_subcategorias(): void
    {
        $parent = $this->product->category->parent;

        Livewire::test(ProductTable::class)
            ->set('filters.category_id', (string) $parent->id)
            ->assertSee($this->product->name);
    }

    public function test_la_toma_fisica_de_una_principal_incluye_sus_subcategorias_y_no_se_solapa(): void
    {
        $parent = $this->product->category->parent;
        $counts = app(InventoryCountService::class);

        $count = $counts->create(['warehouse_id' => $this->warehouse->id, 'category_id' => $parent->id]);
        $this->assertTrue($count->items()->where('product_id', $this->product->id)->exists());

        // La subcategoría comparte productos con la toma de su principal.
        $this->expectException(DomainException::class);
        $counts->create(['warehouse_id' => $this->warehouse->id, 'category_id' => $this->product->category_id]);
    }

    public function test_el_tpv_recibe_principales_con_subcategorias_y_los_mas_vendidos(): void
    {
        $this->openPosSession();
        app(SaleService::class)->create([
            'client_id' => $this->walkinClientId,
            'warehouse_id' => $this->warehouse->id,
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => TipoPago::where('slug', 'efectivo')->value('id'),
            'total_amount' => 300,
            'discount_total' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'price' => 150]],
        ]);

        $parent = $this->product->category->parent;

        Livewire::test(PosWorkspace::class, ['pos_terminal' => $this->terminal])
            ->assertViewHas('topSellerIds', [$this->product->id])
            ->assertViewHas('categories', fn ($tree) => $tree->firstWhere('id', $parent->id)['children']
                ->contains('id', $this->product->category_id))
            ->assertSee('Más vendidos');
    }

    public function test_las_pantallas_de_categorias_cargan(): void
    {
        $this->get(route('configuration.categories.index'))->assertOk();
        $this->get(route('configuration.categories.create'))->assertOk()->assertSee('¿Qué estás creando?');
        $this->get(route('configuration.categories.edit', $this->product->category))->assertOk();
        $this->get(route('inventory.products.create'))->assertOk()->assertSee('<optgroup', false);
    }
}
