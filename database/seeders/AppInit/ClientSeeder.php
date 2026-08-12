<?php

namespace Database\Seeders\AppInit;

use App\Enums\TaxIdentifierType;
use App\Models\Clients\Client;
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
                'is_active' => true,
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

        // La generación de clientes ficticios (antes: Client::factory()->count(30))
        // se muda al comando `zertix:seed-demo` (REQ-07.9, aún no construido) —
        // este seeder es parte del flujo core, no debe crear datos de ejemplo.
    }
}
