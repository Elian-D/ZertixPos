# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ZertixPOS** is an ERP with integrated point of sale for distribution and retail businesses (Dominican Republic market). It manages sales, inventory, accounting, clients, and POS terminals from a single platform, including NCF (Dominican fiscal receipt) compliance.

## Quick Start

**Stack:** Laravel 12 + Livewire 4 + Alpine.js 3 + Tailwind CSS 3 + Vite 7 + MySQL/SQLite

**Database:** Default SQLite for development, MySQL via Docker Compose for production.

**Key dependencies:**
- `spatie/laravel-permission` - Role-based access control
- `livewire/livewire` - Real-time reactive components
- `maatwebsite/excel` - Excel import/export
- `barryvdh/laravel-dompdf` - PDF generation
- `laravel/sail` - Docker development environment

## Running the Application

### Setup (first time)
```bash
composer run setup
```
This command handles: dependency installation, .env setup, key generation, migrations, and npm build.

### Development Server
```bash
composer run dev
```
Starts concurrently: PHP server, queue listener, logging, and Vite dev server on a single terminal.

### Individual Services
```bash
php artisan serve                    # HTTP server (port 8000)
php artisan queue:listen --tries=1   # Job queue processing
php artisan pail --timeout=0         # Real-time logs
npm run dev                          # Vite (Tailwind + JS assets)
```

### Docker Development (Sail)
```bash
./vendor/bin/sail up                 # Start Laravel Sail
./vendor/bin/sail shell              # SSH into container
```
**Never** run `php artisan`/`composer` inside the container with a plain `docker exec` — it runs as `root` and leaves compiled files (`storage/framework/views/*.php`) owned by root, breaking the next request served by the `sail` user with `Permission denied`. Always `docker exec --user=sail <container> php artisan ...`. If it's already broken: `docker exec <container> chown -R sail:sail storage bootstrap/cache`.

## Testing

```bash
composer run test
```

Individual test runs:
```bash
php artisan test tests/Unit/YourTest.php
php artisan test tests/Feature/YourTest.php --filter=methodName
```

Configuration: `phpunit.xml` defines test suites (Unit/Feature) with SQLite in-memory database for testing.

## Code Quality & Linting

```bash
./vendor/bin/pint                    # PHP code formatting/linting
./vendor/bin/pint --test             # Check without modifying
```

## Building Assets

```bash
npm run build                        # Production build (minified)
npm run dev                          # Development build (with source maps)
```

Vite configuration in `vite.config.js` handles Laravel integration with hot module reloading.

---

## Before touching any table/listing screen — read this first

The system is **mid-migration** between two table engines. Getting this wrong produces inconsistent UI or silently broken filters:

- **Livewire engine (`App\Livewire\Base\DataTable`)** — the current standard. Every module in the **Clientes**, **Ventas**, and **Inventario** sidebar groups is migrated to it (Clientes, Cotizaciones, Puntos de Reparto, Equipos, Tipos de Negocio, Tipos de Equipo, Ventas, Terminales POS, Turnos POS, Productos, Categorías, Unidades de Medida, Stock Actual, Movimientos, Almacenes).
- **Legacy AJAX engine** (`app/Tables/*Table.php` static classes + `app/Filters/*` one-class-per-filter + `resources/js/pages/*.js` + `<x-data-table>` with `formId`/`window.filterSources`) — still the live code path for the **Finanzas** and **Sistema** sidebar groups, not yet migrated.
- **The `x-data-table.*` component namespace was deliberately reclaimed for the new Livewire engine** (explicit product decision, not an accident). Its components now expect Livewire props (`filterKey`, `activeCount`, `wire:model.live`), not the old `name`/`formId` props. This means **the legacy AJAX modules' filter UI is currently non-functional** until their own sub-phase migrates them — this is known and accepted (no production deployment yet), not a bug to "fix" in passing.
- **Never build a new table/listing on the legacy AJAX pattern.** Always use the Livewire `DataTable` pattern, even for a module outside the three migrated groups — the legacy pattern is being phased out, not maintained.

**Read `ARCHITECTURE.md` before building or modifying any module** — it documents the current Livewire pattern in full (columns/filters/papelera-as-tab/toast feedback) and a copy-pasteable checklist. It supersedes the "Common Patterns" section further down in this file, which describes the legacy pattern still used by un-migrated Finanzas/Sistema modules.

**Other docs to check before starting non-trivial work, so a fresh session doesn't reintroduce a mistake already fixed once:**
- `docs/ui/datatable-migration-checklist.md` — step-by-step checklist for migrating one more module to Livewire, with real bugs already hit (N+1 patterns, permission gaps when converting a route to a Livewire method, catalog/filter key mismatches) and why each check exists.
- `docs/ui/datatables.md` / `docs/ui/datatable-components.md` — Livewire DataTable engine and component reference.
- `docs/ui/buttons.md`, `docs/ui/badge.md`, `docs/ui/forms.md` — the only sanctioned UI primitives; never hand-roll a `<button>`/badge/form input with raw Tailwind when a `x-ui.*` component covers it.
- `docs/analisis/politica-soft-deletes.md` — how to decide whether a model needs `SoftDeletes`/a papelera tab at all (Categoría A) vs. its own status lifecycle (`cancel()`, B/C) vs. a ledger row that's never deleted (bitácora). Get this wrong and you either build a papelera nobody can reach or delete rows that should never be deletable.
- `docs/analisis/modulos-base-satelite.md`, `docs/analisis/sobre-ingenieria-modulos.md`, `docs/analisis/politica-descuentos.md` — module-boundary and discount-policy decisions referenced by name elsewhere in the code; read before restructuring a module's scope.
- `docs/features/v1.3.0.md` — current roadmap and phase status (Fase 0 = the DataTable migration itself; check its requirement table for what's actually Completed vs. Pendiente before assuming a module's state).

**No dark mode.** `.dark` tokens are reserved in `tailwind.config.js` but nothing renders them — never add `dark:*` Tailwind classes to new markup; there is no dark theme to target yet.

---

## Architecture Overview

### Module Structure (ERP Pattern)

The codebase follows a "Skinny Controllers" pattern with clear separation of concerns:

1. **Database Layer (`app/Models/`)**
   - Each module has models (e.g., `Products/Product.php`, `Sales/Invoice.php`)
   - Each model defines `scopeWithIndexRelations()` to centralize eager loading for both UI tables and Excel exports — and for any per-row method the view calls (`->exists()`, `->count()`, a computed accessor) that would otherwise be an N+1
   - `SoftDeletes` is added only when the model is a real catalog with a borrowable identity (Categoría A per `docs/analisis/politica-soft-deletes.md`) — not on models with their own status lifecycle (`Sale`, `Quote`) or ledger rows (`InventoryMovement`, `PosSession`, `PosCashMovement`)

2. **Listing UI** — two coexisting patterns, see "Before touching any table/listing screen" above:
   - **Migrated modules:** `App\Livewire\App\<Grupo>\<Modulo>Table extends App\Livewire\Base\DataTable`, implementing `columns()`/`filterMap()`/`filterOptions()`/`baseQuery()`/`render()`. Namespace/folder mirrors the module's real route prefix, not the old sidebar grouping.
   - **Not-yet-migrated modules (Finanzas, Sistema):** `app/Tables/*Table.php` static classes (`allColumns()`/`defaultDesktop()`/`defaultMobile()`) + `app/Filters/*` (one class per filter, pipeline via `QueryFilter`) + a controller `index()` that builds the AJAX response.

3. **Request Validation & Authorization (`app/Http/Requests/`)**
   - FormRequest classes handle both validation (`rules()`) and permission checks (`authorize()`)
   - Pattern: `StoreProductRequest`, `UpdateProductRequest` (a `Bulk*Request` only if the module actually has bulk actions wired — see below)
   - All Spatie permission checks happen here before controller execution
   - When an action moves from a route (protected by route middleware) to a Livewire component method, the permission check must be **replicated by hand** inside that method (`abort_unless(auth()->user()->can('...'), 403)`) — the route middleware that used to gate it is gone once the route is deleted

4. **Query Filtering**
   - Migrated modules: closures directly in the Livewire component's `filterMap()` — a filter class only survives if it has real joins/conditional logic that would clutter a closure
   - Un-migrated modules: `app/Filters/<Module>/` pipeline, one class per filter, applied via `(new ModuloFilters($request))->apply(Model::query())`

5. **Service Layer (`app/Services/`)**
   - **CatalogService**: Supplies filtered data for dropdowns/selects (`getForFilters()`, `getForForm()`)
   - **Business Service**: Handles creation, updates, transactions (`create()`, `update()`, `performBulkAction()` if applicable)
   - Services manage `DB::transaction()` and complex logic; controllers never do `Model::create()` directly

6. **HTTP Layer (`app/Http/Controllers/`)**
   - Pure orchestrators. For a migrated module, `index()` is reduced to `return view('modulo.index')` (plus `$this->authorize(...)` if the old route had it) — the Livewire component does everything the old `index()` used to (filters, pagination, catalogs)
   - Grouped by module: `Products/`, `Sales/` (with `Sales/Pos/` and `Sales/Ncf/` sub-namespaces), `Clients/`, `Accounting/`, `Inventory/`
   - `SoftDeletesTrait` (`app/Traits/SoftDeletesTrait.php`) still backs the real `destroy()` route for Categoría A modules — but its `eliminadas()`/`restaurar()`/`borrarDefinitivo()` methods are unreachable dead code on migrated modules (replaced by the papelera-tab + Livewire `restore()`/`forceDelete()`), left in place only because the trait's abstract methods still gate `destroyTrait()`

7. **Routes (`routes/app/*.php`, not `routes/admin/`)**
   - Organized by module, RESTful conventions, permission middleware per route (`permission:view products`)
   - On a migrated module, `eliminados`/`restore`/`borrarDefinitivo`/`bulk`/`toggle-estado` routes are deleted (replaced by Livewire methods); `create`/`store`/`edit`/`update`/`destroy` stay as real routes. `Route::resource(...)` gets `->only([...])` trimmed when the controller has no `create()`/`edit()`/`show()` (modal-based CRUD, common in catalog modules)

8. **Views (`resources/views/`)**
   - Migrated module: a thin wrapper (`<livewire:app.<grupo>.<modulo>-table />`) plus `resources/views/livewire/app/<grupo>/<modulo>-table.blade.php` for the actual table
   - Un-migrated module: Blade templates with the AJAX table + `partials/filters.blade.php`/`partials/table.blade.php` + a `resources/js/pages/<modulo>.js` wiring `AjaxDataTable({...})`
   - Modal-based create/edit/detail partials (`partials/modals.blade.php`) are reused as-is by both patterns — they don't need rewriting when a module migrates, just an `@include`

### Feedback pattern (toasts)

`session()->flash('success', ...)` **only** produces a toast when there's a real page redirect behind it (`x-ui.toasts` reads `session('success')` via Blade in `app-layout.blade.php`, evaluated only on full page load). A Livewire action that doesn't navigate (`restore()`, `forceDelete()`, `toggleActivo()`, `export()`, any `wire:click`) must call `$this->notify('success'|'error'|'info'|'warning', $message)` — the helper on `App\Livewire\Base\DataTable`, which dispatches a browser event `x-ui.toasts` listens for live.

### Papelera (trash) pattern

For a Categoría A model, there is no dedicated `eliminados` route/view anymore — it's an "Activos"/"Papelera" tab on the same index, driven by a `trashed` key in `$filters` that is excluded from chip/active-filter counting (`nonChipFilterKeys()`) and swaps the base query (`Model::onlyTrashed()`), not an added `where`. Permanent deletion uses `x-ui.confirm-deletion-modal` with `:wireConfirm="'forceDelete(' . $id . ')'"` (never `:route` for that specific action, never a native `wire:confirm`).

### Selection / bulk actions

**No module currently has bulk selection enabled**, even where the pre-migration AJAX version had one working (explicit product decision, applies uniformly — do not add it to one module without confirming first). The base engine still supports it (`bulkActions()`, `selected`/`selectAll`, `type: 'select'` value-based actions) for when it's revisited across all modules at once.

### Key Traits & Helpers

- **`HandleStorage`** (`app/Traits/HandleStorage.php`): Image/file upload handling with automatic cleanup
- **`SoftDeletesTrait`** (`app/Traits/SoftDeletesTrait.php`): still backs `destroy()`/`store()`/`update()` real routes; its trash-view methods are vestigial on migrated modules (see above)
- **`general_config()`**: in-request-memoized access to `ConfiguracionGeneral` singleton (currency, logo, etc.) — same `static $var` memoization pattern used elsewhere (e.g. `PosSetting::getSettings()`) to avoid re-hitting the cache store on every call within a request
- **`pos_helper.php`**, **`module_helper.php`**: POS and feature-module utilities

### Permission System

- **Spatie Laravel Permission**: Roles and permissions seeded in `database/seeders/AppInit/PermissionSeeder.php`
- Permission naming convention: `view module`, `create module`, `edit module`, `delete module`, `restore module` (not yet the `recurso.accion` convention proposed for v1.3.0 Fase 2 — still pending)
- Middleware applied per route: `middleware('permission:view products')`
- FormRequests: `$this->user()->can('...')` in `authorize()`
- Livewire component methods that replace a deleted route: `abort_unless(auth()->user()->can('...'), 403)` inside the method itself (see above)

### UI Components

Never hand-roll with raw Tailwind what one of these already covers — see the `docs/ui/*.md` reference for each:

- **`x-ui.*`** (`resources/views/components/ui/`): `button`, `badge`, `action-menu` (+ `action-menu.item` — row actions beyond the single most-prominent one always go here, never as extra loose buttons), `confirm-deletion-modal`, `page-header` (with `:actions`/`:secondary` slots), `empty-state`, `skeleton`, `toasts`, `forms.*` (input/select/textarea/radio)
- **`x-data-table.*`** (`resources/views/components/data-table/`) — **Livewire-engine signatures only** (see the engine split above): `base-table`, `cell`, `filter-container`, `filter-group`, `filter-select`, `filter-date-range`, `filter-range`, `filter-toggle` (boolean switch only — a multi-value filter is `filter-select`, not this), `filter-chips`, `column-selector`, `per-page-selector`, `search`
- A toggle/switch action (activar/desactivar) always lives inside `x-ui.action-menu` as an item, never as a standalone button next to it

### Frontend Stack

- **Tailwind CSS** + **@tailwindcss/forms** for styling — no dark mode (see above)
- **Alpine.js** for reactive components, embedded via `@livewireScripts` (not `@livewireScriptConfig` — a previous manual Alpine/Livewire boot pattern was replaced project-wide because it caused a `$persist` redefinition error and FOUC)
- **Livewire 4** for real-time UI — both the DataTable engine and standalone components (POS terminal workspace, quote builder)
- **Vite** for asset bundling with hot reload

## Module Domains

| Domain | Controllers | Key Models | Purpose |
|--------|-------------|-----------|---------|
| **Products** | `ProductController`, `CategoryController`, `UnitController` | `Product`, `Category`, `Unit` | Catalog management |
| **Inventory** | `InventoryMovementController`, `InventoryStockController`, `WarehouseController` | `InventoryStock`, `InventoryMovement`, `Warehouse` | Stock tracking and movements |
| **Sales** (`Sales/`) | `InvoiceController`, `QuoteController`, `SaleController` | `Invoice`, `Quote`, `QuoteItem`, `Sale`, `SaleItem`, `SalePayment` | Quoting, invoicing, and sale order processing |
| **Sales → POS** (`Sales/Pos/`) | `PosTerminalController`, `PosSessionController`, `PosTerminalLockController`, `PosCashMovementController`, `PosConfigController` | `PosTerminal`, `PosSession`, `PosSetting`, `PosCashMovement` | Terminal sessions, cash drawer movements, PIN-gated access |
| **Sales → NCF** (`Sales/Ncf/`) | `NcfSequenceController`, `NcfTypeController`, `NcfLogController`, `NcfDashboardController` | `NcfSequence`, `NcfType`, `NcfLog` | Dominican fiscal receipt (NCF) compliance — sequence assignment and audit log |
| **Accounting** | `AccountingAccountController`, `JournalEntryController`, `ReceivableController`, `CollectionController` | `Account`, `Journal`, `Receivable`, `ClientCollection` | Double-entry accounting |
| **Clients** | `ClientController`, `EquipmentController`, `PointOfSaleController` (client-side POS reg.) | `Client`, `Equipment` | Customer management, credit limits, field equipment |
| **Configuration** | `ConfiguracionGeneralController`, `TipoPagoController`, `EstadosClienteController` | `ConfiguracionGeneral`, `TipoPago`, etc. | System-wide settings (currency, tax, payment types) |

## Important Architectural Decisions

1. **Eager Loading Centralization**: Models define `scopeWithIndexRelations()` so both index views and exports use the same relations — prevents N+1 problems and ensures consistency. This includes N+1 traps hidden in accessor/helper methods called per row (e.g. a `->exists()` check), not just missing `with()`.

2. **No Business Logic in Controllers**: Services handle all writes, transactions, and complex calculations. Controllers are thin request/response handlers — for a migrated module, `index()` is often nothing but a view return.

3. **FormRequest Authorization**: Permission checks happen at the request level for real routes; Livewire component methods replicate the check by hand since there's no route middleware protecting them.

4. **Query building**: declarative and composable either way — closures in `filterMap()` (Livewire) or filter classes in a `QueryFilter` pipeline (legacy AJAX) — never ad-hoc `where()` chains in the controller.

5. **Soft Deletes are not universal** — only Categoría A models (real catalog identity) get them; see `docs/analisis/politica-soft-deletes.md` before adding `SoftDeletes` to a new model.

6. **Transactions in Services**: Database transactions wrap all write operations in services, ensuring atomicity for complex operations like invoice creation (header + lines + GL entries).

7. **POS Security**: Terminal sessions require PIN validation (RateLimiter enforced), auto-lock on timeout, and mandatory pin for closing cash box. See `AppServiceProvider` for rate limiting and `PosSetting` for security flags.

8. **No bulk actions right now**: deliberately disabled across every migrated module even where the old version had one working — see "Selection / bulk actions" above.

## Database Seeding

Run migrations and seeders:
```bash
php artisan migrate:fresh --seed  # Reset and seed
```

Seeders are organized by domain:
- `PermissionSeeder/`: All permissions for RBAC
- `ProductsSeeders/`: Categories, units, sample products
- `InventorySeeders/`: Warehouses, initial stock
- `SalesSeeders/`: Sequences, NCF config, payment types
- `AccountingSeeders/`: GL accounts, document types
- `ConfigurationSeeders/`: System-wide config (currency, tax, etc.)
- `RoleSeeder.php`, `UserSeeder.php`: Admin user and roles

## Adding a New Module

**Follow `ARCHITECTURE.md`'s checklist, not the legacy pattern below** — it's the current, Livewire-based process (model → `App\Livewire\App\<Grupo>\<Modulo>Table` → wrapper view → thin controller → routes trimmed to what's still real → papelera-as-tab if Categoría A). `docs/ui/datatable-migration-checklist.md` has the exhaustive step-by-step for migrating an *existing* AJAX module to this pattern, with real bugs already caught along the way — read it before doing a migration, not just this summary.

## Notable Features

- **Excel Export**: `app/Exports/` classes powered by Maatwebsite; uses model's `scopeWithIndexRelations()` for consistency. On a migrated module, the export button calls a Livewire `export()` method (`Excel::download()` returned directly from the action) instead of hitting a GET route.
- **PDF Generation**: DomPDF for invoices and reports
- **Observers**: `app/Observers/` watch model changes (e.g., `ReceivableObserver` for aging updates)
- **Listeners**: `app/Listeners/Sales/` handle post-action side effects; GL journal entries are created directly in services (e.g., `JournalEntryService`), not via an events layer — there is no `app/Events/` directory
- **Rate Limiting**: POS PIN attempts limited to 5/minute per terminal via `RateLimiter::for('pos-pin', ...)`
- **DTOs**: `app/DTOs/` for structured data transfer between layers

## Environment

Copy `.env.example` to `.env`. Key variables:
- `DB_CONNECTION=sqlite` (dev) or `mysql` (production)
- `APP_DEBUG=true` (development only)
- `QUEUE_CONNECTION=database` (sync in testing)
- `CACHE_STORE=database` (persistent across requests)
- `SESSION_DRIVER=database` (shared across instances)

## Git Workflow

Feature branches (`feat/<name>` or, during the v1.3.0 DataTable migration, `feature/v1.3.0-datatable-<grupo>`) are merged to `develop`/a `release/*` branch then `main` via pull requests.
