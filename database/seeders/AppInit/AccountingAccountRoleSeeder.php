<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingAccountRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Mapea cada rol al mismo código de cuenta que hoy está hardcodeado
        // en SaleService/InventoryMovementService/ReceivableService/PaymentService/
        // AccountingDashboardController — el resultado de AccountingAccountRole::resolve()
        // debe ser idéntico al de AccountingAccount::where('code', '...') que reemplaza.
        $roles = [
            'cash_default' => '1.1.01',
            'receivable_default' => '1.1.02',
            'inventory_default' => '1.1.03',
            'payable_default' => '2.1',
            'sales_revenue' => '4.1',
            'cost_of_sales' => '5.1',
            'production_cost' => '5.2',
        ];

        foreach ($roles as $role => $code) {
            $accountId = DB::table('accounting_accounts')->where('code', $code)->value('id');

            if (! $accountId) {
                continue;
            }

            DB::table('accounting_account_roles')->updateOrInsert(
                ['role' => $role],
                ['accounting_account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
