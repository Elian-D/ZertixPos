<?php

namespace Database\Seeders\AppInit;

use App\Models\Clients\Client;
use App\Models\Sales\Pos\PosSetting;
use Illuminate\Database\Seeder;

class PosSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscamos al consumidor final por su tax_id genérico definido en tu ClientSeeder
        $defaultClient = Client::where('tax_id', '00000000000')->first();

        PosSetting::firstOrCreate(
            ['id' => 1], // Solo queremos una fila de configuración
            [
                'allow_quick_customer_creation' => true,
                'default_walkin_customer_id' => $defaultClient ? $defaultClient->id : null,
                'allow_quote_without_save' => true,
                'auto_print_receipt' => true,
                'receipt_size' => '80mm',
            ]
        );
    }
}
