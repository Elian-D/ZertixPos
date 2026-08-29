<?php

namespace Database\Seeders\AppInit;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Convención `recurso.accion` (v1.3.0 REQ-2.1/REQ-2.2) — reemplaza los nombres
     * viejos tipo "roles index"/"view products" (mezcla de dos estilos: "recurso
     * accion" y "accion recurso", sin separador consistente). Sin producción
     * desplegada — renombrar acá no requiere migración de datos, solo refrescar
     * el seed (ver docs/features/v1.3.0.md REQ-2.1).
     *
     * REQ-2.2 (reubicaciones, resueltas ANTES del script de propagación de
     * REQ-2.3 — el mapeo de ese script ya sale con estos cambios incluidos):
     * - Cotizaciones: salen de `pos_terminals` (sin relación con manejar caja) a
     *   su propio recurso de primer nivel `quotes`, igual que `invoices` ya es
     *   propio en vez de vivir bajo `sales`.
     * - `pos_points` (permiso viejo `pos *`) renombrado a `delivery_points` — las
     *   rutas ya se habían renombrado así en v1.2.0 Fase 3, el permiso se quedó
     *   atrás generando confusión con `pos_terminals`.
     * - `manage stock` (ahora `products.manage_stock`) se deja SIN fusionar con
     *   `inventory_stocks.*` — posible solape, pendiente de confirmar con el
     *   usuario, no se decide unilateral acá.
     * - `view/create/edit/cancel/delete/export payments` + `print payment
     *   receipts` → recurso `collections`: protegen `CollectionController`
     *   (Cobros), no algo llamado "Payments" — el rename "Pagos"→"Cobros" de
     *   v1.2.0 REQ-4.1 nunca tocó estos nombres a propósito ("problema de datos
     *   aparte", ver el comentario que tenía routes/app/finance.php). Se resuelve
     *   acá, ya que se están renombrando todos los permisos de todos modos.
     *
     * Auditoría de permisos muertos/dormidos (2026-08-28), confirmada con grep
     * contra el código real antes de tocar nada — detalle completo en
     * docs/features/v1.3.0.md §2.2:
     * - Eliminados (cero uso en todo el proyecto): products.manage_stock,
     *   receivables.cancel/report, collections.edit/delete/print_receipt,
     *   invoices.print, sales.print_receipt, ncf.void, pos_cash_movements.history,
     *   delivery_points.regenerate-code, equipment.regenerate-code (el campo
     *   `code` que regeneraban se eliminó por completo de Equipment/PointOfSale,
     *   ver REQ-2.2).
     * - Comentados, no eliminados (dormidos detrás de `module:accounting.advanced`,
     *   apagado por defecto — reactivables sin adivinar el nombre de nuevo):
     *   accounting.configure, accounting_accounts.manage, journal_entries.*.
     *   `accounting.dashboard` NO se comentó — también protege
     *   FinancialOverviewController ("Ingresos y Gastos"), activo de verdad.
     * - pos_cash_movements.create se queda activo (su ruta está comentada en
     *   routes/app/sales.php, no borrada) pero se excluye a mano de la vista de
     *   gestión de permisos (REQ-2.6) hasta que esa ruta se reactive.
     *
     * REQ-2.5 (revisado 2026-08-28): cada key del array de abajo es también la
     * key de un `PermissionGroup` real en BD — reemplaza el str_starts_with()
     * heurístico que RoleController::editPermissions() usaba para agrupar
     * permisos (mandaba casi todo a "Otros" por no matchear ningún prefijo
     * hardcodeado). El primer intento agrupó 1:1 por recurso `recurso.accion`
     * (30 grupos reales, 11 de ellos con un solo permiso) — demasiado ruido en
     * la vista de asignación de permisos (una tarjeta por grupo). Se consolida
     * por **dominio de módulo** (Opción A discutida con el usuario, calca la
     * tabla "Module Domains" de CLAUDE.md) — 8 grupos, ninguno de un solo
     * permiso. El orden de GROUP_LABELS es el orden real en que se agrupan en
     * la vista (`sort_order` se asigna por posición en el foreach de `run()`).
     * Los `PermissionGroup` de la agrupación 1:1 anterior que ya no se generan
     * acá se purgan al final de `run()` (`whereNotIn('key', ...)`) — sin
     * producción desplegada, no hace falta conservarlos vacíos.
     */
    private const GROUP_LABELS = [
        'system' => 'Sistema',
        'clients' => 'Clientes',
        'products' => 'Productos',
        'inventory' => 'Inventario',
        'sales' => 'Ventas',
        'pos' => 'Punto de Venta',
        'ncf' => 'NCF',
        'accounting' => 'Contabilidad',
    ];

    /**
     * REQ-2.7 punto 5 — mapea cada permiso de un módulo satélite/flexible a su
     * `module_key` real de `config/modules.php`. Construido leyendo el
     * middleware `module:<key>` REALMENTE aplicado en `routes/app/*.php`
     * (única fuente de verdad — el `route_prefixes` de `config/modules.php` es
     * solo informativo, no lo que gatea de verdad), no supuesto por nombre de
     * recurso. Cualquier permiso ausente de este mapa es núcleo (siempre
     * visible/asignable, `module_key = null`).
     *
     * Auditado 2026-08-28 contra `routes/app/{clients,inventory,finance,quotes}.php`:
     * - `sales.delivery_points`: `delivery_points.*`, `business_types.manage`
     *   (routes/app/clients.php:12,109).
     * - `clients.field_assets`: `equipment.*`, `equipment_types.manage`
     *   (routes/app/clients.php:62).
     * - `inventory.tracking`: `warehouses.manage`, `inventory.dashboard`,
     *   `inventory_stocks.*`, `inventory_movements.*` (routes/app/inventory.php,
     *   routes/app/reports.php:28 — NO incluye products/categories/units, ver el
     *   fix de nesting aplicado hoy mismo en routes/app/inventory.php).
     * - `sales.receivables`: `receivables.*`, `collections.*` (mismo flag para
     *   ambos — routes/app/finance.php:80,104, "sin CxC no hay nada que cobrar").
     * - `sales.quotes`: `quotes.*` (routes/app/quotes.php:20).
     * - `sales.ncf`: `ncf_sequences.*`, `ncf.reports`, `ncf_types.manage`
     *   (routes/app/finance.php:190).
     *
     * `accounting.dashboard` NO entra acá a propósito, aunque una de sus dos
     * rutas (`reports.finance`) sí está detrás de `module:accounting.advanced`
     * — también protege `FinancialOverviewController` ("Ingresos y Gastos"),
     * sin gate, real y siempre activa (mismo razonamiento ya documentado más
     * abajo en el array `$permissions`). `accounting_accounts.manage`/
     * `journal_entries.*` tampoco entran — están comentados, ni siquiera
     * existen como `Permission` hoy.
     */
    private const MODULE_KEYS = [
        'delivery_points.view' => 'sales.delivery_points',
        'delivery_points.create' => 'sales.delivery_points',
        'delivery_points.edit' => 'sales.delivery_points',
        'delivery_points.delete' => 'sales.delivery_points',
        'delivery_points.restore' => 'sales.delivery_points',
        'business_types.manage' => 'sales.delivery_points',

        'equipment.view' => 'clients.field_assets',
        'equipment.create' => 'clients.field_assets',
        'equipment.edit' => 'clients.field_assets',
        'equipment.delete' => 'clients.field_assets',
        'equipment.restore' => 'clients.field_assets',
        'equipment_types.manage' => 'clients.field_assets',

        'warehouses.manage' => 'inventory.tracking',
        'inventory.dashboard' => 'inventory.tracking',
        'inventory_stocks.view' => 'inventory.tracking',
        'inventory_stocks.update' => 'inventory.tracking',
        'inventory_stocks.export' => 'inventory.tracking',
        'inventory_movements.view' => 'inventory.tracking',
        'inventory_movements.create_adjustment' => 'inventory.tracking',

        'receivables.view' => 'sales.receivables',
        'receivables.create' => 'sales.receivables',
        'receivables.edit' => 'sales.receivables',
        'collections.view' => 'sales.receivables',
        'collections.create' => 'sales.receivables',
        'collections.cancel' => 'sales.receivables',
        'collections.export' => 'sales.receivables',

        'quotes.view' => 'sales.quotes',
        'quotes.create' => 'sales.quotes',
        'quotes.edit' => 'sales.quotes',
        'quotes.convert' => 'sales.quotes',
        'quotes.cancel' => 'sales.quotes',

        'ncf_sequences.view' => 'sales.ncf',
        'ncf_sequences.manage' => 'sales.ncf',
        'ncf.reports' => 'sales.ncf',
        'ncf_types.manage' => 'sales.ncf',
    ];

    public function run(): void
    {
        $permissions = [
            'system' => [
                'dashboard.view',

                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                // 'roles.assign' eliminado (REQ-2.7 punto 6) — gateaba la pantalla de
                // "asignar permisos" aparte (editPermissions()/updatePermissions()),
                // que se elimina: los permisos se seleccionan directo en create/edit,
                // ya gateados por roles.create/roles.edit. Sin otro uso, huérfano.

                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.assign',

                'config.view',
                'config.general',
                'config.payment_types',
                'config.modules',
            ],

            'clients' => [
                'clients.view',
                'clients.create',
                'clients.edit',
                'clients.delete',
                'clients.restore',

                'delivery_points.view',
                'delivery_points.create',
                'delivery_points.edit',
                'delivery_points.delete',
                'delivery_points.restore',
                // 'delivery_points.regenerate-code' eliminado (REQ-2.2) — el campo
                // `code` que regeneraba se quitó por completo del modelo.

                'equipment.view',
                'equipment.create',
                'equipment.edit',
                'equipment.delete',
                'equipment.restore',
                // 'equipment.regenerate-code' eliminado (REQ-2.2) — mismo motivo.

                'business_types.manage',
                'equipment_types.manage',
            ],

            'products' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'products.restore',
                // 'products.manage_stock' eliminado (REQ-2.2) — cero uso en el
                // proyecto, posible solape con inventory_stocks.* nunca resuelto.

                'categories.manage',
                'units.manage',
            ],

            'inventory' => [
                'warehouses.manage',
                'inventory.dashboard',

                'inventory_stocks.view',
                'inventory_stocks.update',
                'inventory_stocks.export',

                'inventory_movements.view',
                'inventory_movements.create_adjustment',
            ],

            'sales' => [
                'sales.view',
                'sales.create',
                'sales.edit',
                'sales.cancel',
                'sales.delete',
                'sales.export',
                // 'sales.print_receipt' eliminado (REQ-2.2) — cero uso.

                'invoices.view',
                'invoices.export',
                // 'invoices.print' eliminado (REQ-2.2) — cero uso.

                'quotes.view',
                'quotes.create',
                'quotes.edit',
                'quotes.convert',
                'quotes.cancel',
            ],

            'pos' => [
                'pos_terminals.view',
                'pos_terminals.create',
                'pos_terminals.edit',
                'pos_terminals.delete',

                'pos_sessions.manage',
                'pos_sessions.history',

                // Ruta comentada en routes/app/sales.php — feature dormida, no
                // borrada. Este permiso se excluye a mano de la vista de gestión
                // de permisos (REQ-2.6) hasta que se reactive.
                'pos_cash_movements.create',
                // 'pos_cash_movements.history' eliminado (REQ-2.2) — cero uso.

                'pos_config.view',
                'pos_config.update',
            ],

            'ncf' => [
                'ncf_sequences.view',
                'ncf_sequences.manage',

                // 'ncf.void' eliminado (REQ-2.2) — cero uso, sin acción real detrás.
                'ncf.reports',

                'ncf_types.manage',
            ],

            'accounting' => [
                'accounting.dashboard', // también protege FinancialOverviewController, real y activo — no comentar
                // 'accounting.configure' comentado (REQ-2.2) — detrás de
                // module:accounting.advanced, apagado por defecto.

                // Comentado en bloque (REQ-2.2) — accounting_accounts y journal_entries
                // están 100% detrás de module:accounting.advanced (apagado por defecto,
                // el sidebar no muestra nada de este grupo). Código real sigue existiendo
                // (Plan de Cuentas/Asientos), se reactiva sin adivinar el nombre otra vez
                // el día que ese módulo se retome — quedaría dentro de este mismo grupo
                // 'accounting' (Contabilidad), no aparte.
                // 'accounting_accounts.manage',
                // 'journal_entries.view',
                // 'journal_entries.create',
                // 'journal_entries.edit',
                // 'journal_entries.post',
                // 'journal_entries.cancel',
                // 'journal_entries.delete',

                'document_types.view',
                'document_types.edit',

                'receivables.view',
                'receivables.create',
                'receivables.edit',
                // 'receivables.cancel' eliminado (REQ-2.2) — nunca se chequea en
                // código real, solo mencionado en un comentario; la acción real la
                // protege sales.cancel (cancelar la venta cascadea a la CxC).
                // 'receivables.report' eliminado (REQ-2.2) — cero uso.

                'collections.view',
                'collections.create',
                // 'collections.edit'/'collections.delete'/'collections.print_receipt'
                // eliminados (REQ-2.2) — cero uso, sin UI de editar/borrar Cobros
                // (Categoría C) y sin sentido un permiso propio solo para imprimir.
                'collections.cancel',
                'collections.export',
            ],
        ];

        $order = 0;
        foreach ($permissions as $key => $names) {
            $group = PermissionGroup::updateOrCreate(
                ['key' => $key],
                ['label' => self::GROUP_LABELS[$key] ?? ucfirst($key), 'sort_order' => $order++],
            );

            foreach ($names as $name) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'permission_group_id' => $group->id,
                        'module_key' => self::MODULE_KEYS[$name] ?? null,
                    ],
                );
            }
        }

        // Purga los `PermissionGroup` de la agrupación 1:1 por recurso anterior
        // (30 grupos, ver REQ-2.5 arriba) que ya no genera este seeder — sin
        // producción desplegada, no hace falta conservarlos vacíos. `nullOnDelete()`
        // en `permissions.permission_group_id` hace este borrado seguro aunque
        // algún permiso todavía apuntara a uno de ellos (no debería, ya se
        // reasignaron arriba).
        PermissionGroup::whereNotIn('key', array_keys(self::GROUP_LABELS))->delete();
    }
}
