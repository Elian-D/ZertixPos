<?php

namespace Tests\Feature\V151;

use App\Livewire\App\Clients\ClientTable;
use App\Livewire\App\Inventory\CategoryTable;
use App\Livewire\App\Inventory\WarehouseTable;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Category;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleService;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

/**
 * v1.5.1 Fase 1: borrar definitivo nunca da un 500 ni borra datos en silencio, y los
 * errores puntuales (equipos, NCF con RNC).
 */
class IntegrityTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPosWorkspace();
        $this->actingAs($this->cashier);
    }

    private function sellTo(int $clientId, ?int $ncfTypeId = null): Sale
    {
        return app(SaleService::class)->create([
            'client_id' => $clientId,
            'warehouse_id' => $this->warehouse->id,
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => TipoPago::where('slug', 'efectivo')->value('id'),
            'ncf_type_id' => $ncfTypeId,
            'total_amount' => 150,
            'discount_total' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 150]],
        ]);
    }

    public function test_cliente_con_ventas_no_se_borra_definitivo_y_avisa_que_lo_ata(): void
    {
        $client = Client::factory()->create(['name' => 'ZZ Cliente', 'balance' => 0]);
        $this->sellTo($client->id);
        $client->delete();

        Livewire::test(ClientTable::class)
            ->call('forceDelete', $client->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error'
                && str_contains($params['message'], 'tiene 1 venta'));

        $this->assertNotNull(Client::onlyTrashed()->find($client->id));
    }

    public function test_categoria_con_productos_no_se_borra_y_sin_productos_si(): void
    {
        $used = $this->product->category;
        $used->delete();

        Livewire::test(CategoryTable::class)
            ->call('forceDelete', $used->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error'
                && str_contains($params['message'], 'producto'));

        $this->assertNotNull($this->product->fresh(), 'borrar la categoría no puede borrar sus productos');

        $empty = Category::create(['name' => 'ZZ Vacía']);
        $empty->delete();

        Livewire::test(CategoryTable::class)
            ->call('forceDelete', $empty->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $this->assertNull(Category::withTrashed()->find($empty->id));
    }

    public function test_almacen_con_existencias_no_se_borra_definitivo(): void
    {
        $this->warehouse->delete();

        Livewire::test(WarehouseTable::class)
            ->call('forceDelete', $this->warehouse->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error'
                && str_contains($params['message'], 'con existencia'));

        $this->assertNotNull(Warehouse::onlyTrashed()->find($this->warehouse->id));
    }

    public function test_ninguna_llave_hacia_un_catalogo_borra_en_cascada(): void
    {
        $catalogs = ['accounting_accounts', 'business_types', 'categories', 'clients', 'equipment_types',
            'point_of_sales', 'pos_terminals', 'products', 'units', 'users', 'vendors', 'warehouses'];

        // Filas que son parte del registro: sí pueden irse con él.
        $partOf = ['inventory_stocks', 'product_taxes'];

        $cascades = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('DELETE_RULE', 'CASCADE')
            ->whereIn('REFERENCED_TABLE_NAME', $catalogs)
            ->whereNotIn('TABLE_NAME', $partOf)
            ->pluck('CONSTRAINT_NAME');

        $this->assertSame([], $cascades->all());
    }

    public function test_crear_equipo_carga(): void
    {
        $this->enableModule('clients.field_assets');

        $this->get(route('clients.equipment.create'))->assertOk();
    }

    public function test_comprobante_con_rnc_no_sale_a_un_cliente_sin_rnc_aunque_no_pase_por_el_request(): void
    {
        $this->enableModule('sales.ncf');
        $b01 = NcfType::where('prefix', 'B')->where('code', '01')->firstOrFail();
        $client = Client::factory()->create(['tax_id' => null]);

        try {
            $this->sellTo($client->id, $b01->id);
            $this->fail('La venta con B01 a un cliente sin RNC no debió salir.');
        } catch (Exception $e) {
            $this->assertSame($b01->missingRncMessage(), $e->getMessage());
            $this->assertStringContainsString('(B01)', $e->getMessage());
        }
    }
}
