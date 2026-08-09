<?php

namespace Database\Factories\Clients;

use App\Enums\TaxIdentifierType;
use App\Models\Accounting\AccountingAccount;
use App\Models\Clients\Client;
use App\Models\Configuration\EstadosCliente;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    /**
     * Define el estado básico del modelo.
     * ESTO ES LO QUE FALTABA
     */
    public function definition(): array
    {
        $type = fake()->boolean(50) ? 'individual' : 'company';
        $provinciaId = Province::inRandomOrder()->value('id') ?? 1;

        return [
            'type' => $type,
            'estado_cliente_id' => EstadosCliente::inRandomOrder()->value('id') ?? 1,
            'name' => $type === 'individual' ? fake()->name() : fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'provincia_id' => $provinciaId,
            'municipio_id' => Municipality::where('province_id', $provinciaId)->inRandomOrder()->value('id'),
            'address' => fake()->address(),
            'tax_identifier_type' => fake()->randomElement(TaxIdentifierType::cases())->value,
            'tax_id' => fake()->numerify('###########'),
            'credit_limit' => fake()->randomElement([5000, 10000, 20000]),
            'balance' => 0, // Empezamos en 0 para que afterCreating cree la deuda real
            'payment_terms' => 30,
            'accounting_account_id' => null, // Se asigna en configure()
        ];
    }

    /**
     * Solo asegura la cuenta contable (CxC) del cliente. La generación de
     * CxC/pagos de ejemplo con fechas dispersas ya no vive aquí — la genera
     * explícitamente app/Console/Commands/SeedDemoData.php (REQ-07.10), usando
     * los servicios reales (ReceivableService/PaymentService) en vez de un
     * efecto secundario escondido con `payment_date => now()` fijo.
     */
    public function configure()
    {
        return $this->afterCreating(function (Client $client) {
            if ($client->name === 'Consumidor Final') {
                return;
            }

            if (! $client->accounting_account_id) {
                $client->update([
                    'accounting_account_id' => AccountingAccount::where('code', '1.1.02')->first()?->id,
                ]);
            }
        });
    }
}
