<?php

namespace Database\Factories\Clients;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\DocumentType;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Configuration\EstadosCliente;
use App\Models\Configuration\TaxIdentifierType;
use App\Models\Geo\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected int $countryId;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        // Asegúrate de que esta función helper exista o usa un valor por defecto
        $this->countryId = function_exists('general_config') ? general_config()->country_id : 1;
    }

    /**
     * Define el estado básico del modelo.
     * ESTO ES LO QUE FALTABA
     */
    public function definition(): array
    {
        $type = fake()->boolean(50) ? 'individual' : 'company';

        return [
            'type' => $type,
            'estado_cliente_id' => EstadosCliente::inRandomOrder()->value('id') ?? 1,
            'name' => $type === 'individual' ? fake()->name() : fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'state_id' => State::where('country_id', $this->countryId)->inRandomOrder()->value('id') ?? 1,
            'city' => fake()->city(),
            'address' => fake()->address(),
            'tax_identifier_type_id' => TaxIdentifierType::where('country_id', $this->countryId)->inRandomOrder()->value('id') ?? 1,
            'tax_id' => fake()->numerify('###########'),
            'credit_limit' => fake()->randomElement([5000, 10000, 20000]),
            'balance' => 0, // Empezamos en 0 para que afterCreating cree la deuda real
            'payment_terms' => 30,
            'accounting_account_id' => null, // Se asigna en configure()
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Client $client) {
            // Si es el Consumidor Final, no generamos deudas de prueba
            if ($client->name === 'Consumidor Final') {
                return;
            }

            // 1. Asegurar cuenta contable (CxC)
            if (! $client->accounting_account_id) {
                $client->update([
                    'accounting_account_id' => AccountingAccount::where('code', '1.1.02')->first()?->id,
                ]);
            }

            // 2. Generar facturas (Receivables) reales solo para clientes con crédito
            Receivable::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create([
                    'client_id' => $client->id,
                    'accounting_account_id' => $client->accounting_account_id,
                ])
                ->each(function ($receivable) use ($client) {
                    // 3. Crear pagos parciales
                    if (fake()->boolean(70)) {
                        $amountToPay = fake()->randomFloat(2, 10, $receivable->total_amount * 0.5);
                        $this->createRealPayment($client, $receivable, $amountToPay);
                    }
                });

            // 4. Sincronizar balance
            $client->refreshBalance();
        });
    }

    protected function createRealPayment($client, $receivable, $amount)
    {
        // Buscamos el tipo de documento cada vez para asegurar frescura
        $docType = DocumentType::where('code', 'PAG')->first();

        if ($docType) {
            // Usamos el método formateado
            $receiptNumber = $docType->getNextNumberFormatted();
            // Incrementamos directamente en la DB para que el siguiente factory vea el nuevo número
            $docType->increment('current_number');
        } else {
            // Fallback mucho más robusto si no existe el tipo de documento
            $receiptNumber = 'REC-'.str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT);
        }

        Payment::create([
            'client_id' => $client->id,
            'receivable_id' => $receivable->id,
            'tipo_pago_id' => \App\Models\Configuration\TipoPago::where('slug', 'efectivo')->value('id')
                ?? \App\Models\Configuration\TipoPago::query()->value('id'),
            'receipt_number' => $receiptNumber,
            'amount' => $amount,
            'payment_date' => now(),
            'status' => 'active',
            'created_by' => \App\Models\User::query()->value('id'),
        ]);

        $receivable->decrement('current_balance', $amount);
    }
}
