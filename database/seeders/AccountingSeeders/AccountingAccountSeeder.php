<?php

namespace Database\Seeders\AccountingSeeders;

use App\Models\Accounting\AccountingAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingAccountSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // ACTIVOS
            ['code' => '1', 'name' => 'Activos', 'type' => AccountingAccount::TYPE_ASSET, 'is_selectable' => false, 'level' => 1],
            ['code' => '1.1', 'name' => 'Activos Corrientes', 'type' => AccountingAccount::TYPE_ASSET, 'is_selectable' => false, 'level' => 2, 'parent_code' => '1'],
            ['code' => '1.1.01', 'name' => 'Caja y Bancos', 'type' => AccountingAccount::TYPE_ASSET, 'is_selectable' => true, 'level' => 3, 'parent_code' => '1.1'],
            ['code' => '1.1.02', 'name' => 'Cuentas por Cobrar', 'type' => AccountingAccount::TYPE_ASSET, 'is_selectable' => true, 'level' => 3, 'parent_code' => '1.1'],
            ['code' => '1.1.03', 'name' => 'Inventarios', 'type' => AccountingAccount::TYPE_ASSET, 'is_selectable' => true, 'level' => 3, 'parent_code' => '1.1'],

            // PASIVOS (Lo que debemos)
            ['code' => '2', 'name' => 'Pasivos', 'type' => 'liability', 'is_selectable' => false, 'level' => 1],
            ['code' => '2.1', 'name' => 'Pasivos Corrientes', 'type' => 'liability', 'is_selectable' => false, 'level' => 2, 'parent_code' => '2'],
            ['code' => '2.1.01', 'name' => 'Cuentas por Pagar Comerciales', 'type' => 'liability', 'is_selectable' => true, 'level' => 3, 'parent_code' => '2.1'],
            ['code' => '2.1.02', 'name' => 'Servicios por Pagar (Luz, Agua)', 'type' => 'liability', 'is_selectable' => true, 'level' => 3, 'parent_code' => '2.1'],
            // ['code' => '2.1.03', 'name' => 'Impuestos por Pagar', 'type' => 'liability', 'is_selectable' => true, 'level' => 3, 'parent_code' => '2.1'],


            // PATRIMONIO (Nivel 1 y 2)
            ['code' => '3', 'name' => 'Patrimonio', 'type' => 'equity', 'is_selectable' => false, 'level' => 1],
            ['code' => '3.1', 'name' => 'Capital', 'type' => 'equity', 'is_selectable' => false, 'level' => 2, 'parent_code' => '3'],
            ['code' => '3.1.01', 'name' => 'Capital Social (Aportes)', 'type' => 'equity', 'is_selectable' => true, 'level' => 3, 'parent_code' => '3.1'],
            ['code' => '3.1.02', 'name' => 'Resultados Acumulados', 'type' => 'equity', 'is_selectable' => true, 'level' => 3, 'parent_code' => '3.1'],

            // INGRESOS
            ['code' => '4', 'name' => 'Ingresos', 'type' => AccountingAccount::TYPE_REVENUE, 'is_selectable' => false, 'level' => 1],
            ['code' => '4.1', 'name' => 'Ventas de Productos', 'type' => AccountingAccount::TYPE_REVENUE, 'is_selectable' => true, 'level' => 2, 'parent_code' => '4'],
            ['code' => '4.2', 'name' => 'Otros Ingresos', 'type' => 'revenue', 'is_selectable' => false, 'level' => 2, 'parent_code' => '4'],
            ['code' => '4.2.01', 'name' => 'Sobrantes de Caja', 'type' => 'revenue', 'is_selectable' => true, 'level' => 3, 'parent_code' => '4.2'],
            
            // COSTOS
            ['code' => '5', 'name' => 'Costos', 'type' => AccountingAccount::TYPE_COST, 'is_selectable' => false, 'level' => 1],
            ['code' => '5.1', 'name' => 'Costo de Ventas', 'type' => AccountingAccount::TYPE_COST, 'is_selectable' => true, 'level' => 2, 'parent_code' => '5'],
            // COSTOS DE PRODUCCIÓN (Para las entradas de hielo fabricado)
            ['code' => '5.2', 'name' => 'Costos de Producción/Transformación', 'type' => 'cost', 'is_selectable' => true, 'level' => 2, 'parent_code' => '5'],
            // GASTOS ADMINISTRATIVOS (No son costo de ventas)
            ['code' => '5.3', 'name' => 'Gastos Administrativos', 'type' => 'expense', 'is_selectable' => false, 'level' => 2, 'parent_code' => '5'],
            ['code' => '5.3.01', 'name' => 'Servicios Eléctricos', 'type' => 'expense', 'is_selectable' => true, 'level' => 3, 'parent_code' => '5.3'],
            ['code' => '5.3.02', 'name' => 'Suministros (Fundas, insumos)', 'type' => 'expense', 'is_selectable' => true, 'level' => 3, 'parent_code' => '5.3'],
            ['code' => '5.3.03', 'name' => 'Mantenimiento y Reparaciones', 'type' => 'expense', 'is_selectable' => true, 'level' => 3, 'parent_code' => '5.3'],
            ['code' => '5.3.04', 'name' => 'Faltantes de Caja', 'type' => 'expense', 'is_selectable' => true, 'level' => 3, 'parent_code' => '5.3'],
        ];

        foreach ($accounts as $acc) {
            $parentId = isset($acc['parent_code']) 
                ? DB::table('accounting_accounts')->where('code', $acc['parent_code'])->value('id') 
                : null;

            DB::table('accounting_accounts')->insert([
                'parent_id' => $parentId,
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'level' => $acc['level'],
                'is_selectable' => $acc['is_selectable'],
                'created_at' => now(),
            ]);
        }
    }
}
