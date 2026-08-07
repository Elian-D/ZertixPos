<?php

namespace Database\Seeders\ClientsConfigSeeders;

use App\Enums\TaxIdentifierType;
use App\Models\Clients\Client;
use App\Models\Configuration\EstadosCliente;
use App\Models\Geo\Province;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $provinciaId = Province::where('name', 'Distrito Nacional')->value('id')
            ?? Province::query()->value('id');

        // 1. Crear el Cliente Genérico (Consumidor Final)
        Client::firstOrCreate(
            ['tax_id' => '00000000000'], // Identificador genérico
            [
                'type' => 'individual',
                'estado_cliente_id' => EstadosCliente::where('nombre', 'Activo')->value('id') ?? 1,
                'name' => 'Consumidor Final',
                'email' => 'consumidor@final.com',
                'phone' => '0000000000',
                'provincia_id' => $provinciaId,
                'municipio_id' => null,
                'address' => 'Ventas de Mostrador',
                'tax_identifier_type' => TaxIdentifierType::CEDULA->value,
                'credit_limit' => 0, // No tiene crédito
                'balance' => 0,
                'payment_terms' => 0,
                'accounting_account_id' => null,
            ]
        );

        // 2. Crear los 30 clientes aleatorios mediante Factory
        Client::factory()->count(30)->create();
    }
}
