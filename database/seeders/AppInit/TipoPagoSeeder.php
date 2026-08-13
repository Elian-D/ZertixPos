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
            // "Crédito" es un slug interno protegido (TipoPago::CREDITO, ver isSystemProtected()),
            // usado solo para dejar rastro contable en ventas a CxC. Nunca debe ofrecerse
            // como método de pago seleccionable, así que se mantiene siempre inactivo.
            ['nombre' => 'Crédito', 'account_id' => null, 'estado' => false],
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

        // "Nota de Crédito Aplicada" no es un método de pago real (nunca se usó en el
        // código) y confundía al cajero en el selector. Se desactiva en vez de borrarse
        // físicamente por si alguna venta histórica ya la referencia.
        TipoPago::where('slug', 'nota-de-credito-aplicada')->update(['estado' => false]);
    }
}
