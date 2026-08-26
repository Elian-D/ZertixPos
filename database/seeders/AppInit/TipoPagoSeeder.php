<?php

namespace Database\Seeders\AppInit;

use App\Models\Configuration\TipoPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TipoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $cajaId = DB::table('accounting_accounts')->where('code', '1.1.01')->value('id');

        // Orden = jerarquía real de uso en caja: efectivo, tarjeta y transferencia primero
        // (los métodos del día a día), depósito y cheque después (menos frecuentes,
        // requieren conciliación bancaria manual).
        $tiposPago = [
            ['nombre' => 'Efectivo', 'account_id' => $cajaId, 'estado' => true],
            ['nombre' => 'Tarjeta', 'account_id' => $cajaId, 'estado' => true],
            ['nombre' => 'Transferencia', 'account_id' => $cajaId, 'estado' => true],
            ['nombre' => 'Depósito', 'account_id' => $cajaId, 'estado' => true],
            // Cheque: desactivado por defecto. Muchos negocios ya no lo aceptan;
            // el admin lo activa desde Configuración > Tipos de Pago si lo necesita.
            ['nombre' => 'Cheque', 'account_id' => $cajaId, 'estado' => false],
        ];

        foreach ($tiposPago as $tipo) {
            TipoPago::updateOrCreate(
                ['slug' => Str::slug($tipo['nombre'])], // Buscamos por slug, no por nombre
                [
                    'nombre' => $tipo['nombre'],
                    'estado' => $tipo['estado'],
                    'accounting_account_id' => $tipo['account_id'],
                ]
            );
        }
    }
}
