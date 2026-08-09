<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Consolidado de los 13 archivos que antes vivían en PermissionSeeder/*.php —
     * un array por sección, mismos permisos exactos (ningún nombre cambia, ver
     * docs/features/v1.1.0.md §7.7). El grupo 'geography' (provincias/municipios/
     * sectores) del PermissionSeeder original se eliminó por completo: no tenía
     * ninguna ruta/middleware real detrás, 'sectores' ni siquiera existe como
     * concepto en el sistema (Fase 6 solo trabaja con provincias/municipios).
     */
    public function run(): void
    {
        $permissions = [
            'dashboard' => [
                'view dashboard',
            ],

            'roles' => [
                'roles index',
                'roles create',
                'roles edit',
                'roles delete',
                'roles assign',
            ],

            'users' => [
                'users index',
                'users create',
                'users edit',
                'users delete',
                'users assign',
            ],

            'config' => [
                'view configuration',
                'configure general data',
                'configure payments',
                'configure client-states',
                'configure dias-semana',
            ],

            'clients' => [
                'configure business types',
                'configure equipment types',
                'clients index',
                'clients create',
                'clients edit',
                'clients delete',
                'clients restore',
            ],

            'pos_points' => [
                'pos index',
                'pos create',
                'pos edit',
                'pos delete',
                'pos restore',
                'pos regenerate-code',
            ],

            'equipment' => [
                'equipment index',
                'equipment create',
                'equipment edit',
                'equipment delete',
                'equipment restore',
                'equipment regenerate-code',
            ],

            'products' => [
                'configure categories',
                'configure units',
                'view products',
                'create products',
                'edit products',
                'delete products',
                'restore products',
                'manage stock',
            ],

            'inventory' => [
                'view inventory dashboard',
                'configure warehouses',
                'inventory stocks index',
                'inventory stocks update',
                'inventory stocks export',
                'view inventory movements',
                'create inventory adjustments',
            ],

            'accounting' => [
                'view accounting dashboard',
                'configure accounting',
                'configure accounting account',
                'view journal entries',
                'create journal entries',
                'edit journal entries',
                'post journal entries',
                'cancel journal entries',
                'delete journal entries',
                'view document types',
                'create document types',
                'edit document types',
                'delete document types',
                'view receivables',
                'create receivables',
                'edit receivables',
                'cancel receivables',
                'report receivables',
                'view payments',
                'create payments',
                'edit payments',
                'cancel payments',
                'delete payments',
                'export payments',
                'print payment receipts',
            ],

            'sales' => [
                'view sales',
                'create sales',
                'edit sales',
                'cancel sales',
                'delete sales',
                'export sales',
                'print sales receipts',
                'view invoices',
                'export invoices',
                'print invoices',
                'view ncf sequences',
                'manage ncf sequences',
                'void ncf',
                'view ncf reports',
                'manage ncf types',
            ],

            'pos_terminals' => [
                'view pos terminals',
                'create pos terminals',
                'edit pos terminals',
                'delete pos terminals',
                'pos sessions manage',
                'pos sessions history',
                'pos cash movements create',
                'pos cash movements history',
                'pos config view',
                'pos config update',
                'view quotes',
                'create quotes',
                'edit quotes',
                'convert quotes',
                'cancel quotes',
            ],
        ];

        foreach ($permissions as $names) {
            foreach ($names as $name) {
                Permission::firstOrCreate(['name' => $name]);
            }
        }
    }
}
