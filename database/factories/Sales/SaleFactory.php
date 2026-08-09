<?php

namespace Database\Factories\Sales;

use App\Models\Accounting\DocumentType;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Uso general de pruebas (tests, tinker) — crea una fila de Sale plana, sin
     * pasar por SaleService::create(). No mueve inventario ni genera CxC/asientos
     * reales; para historial demo consistente con las reglas de negocio, usa
     * SaleService::create() directamente (ver app/Console/Commands/SeedDemoData.php).
     */
    public function definition(): array
    {
        $paymentType = fake()->randomElement([Sale::PAYMENT_CASH, Sale::PAYMENT_CREDIT]);
        $docType = DocumentType::where('code', 'FAC')->first();

        return [
            'document_type_id' => $docType?->id,
            'number' => 'FAC-'.fake()->unique()->numerify('######'),
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'warehouse_id' => Warehouse::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'sale_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'total_amount' => fake()->randomFloat(2, 200, 5000),
            'discount_total' => 0,
            'payment_type' => $paymentType,
            'tipo_pago_id' => $paymentType === Sale::PAYMENT_CASH
                ? TipoPago::query()->inRandomOrder()->value('id')
                : null,
            'cash_received' => 0,
            'cash_change' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'sale_origin' => 'backoffice',
            'is_walkin_customer' => false,
        ];
    }
}
