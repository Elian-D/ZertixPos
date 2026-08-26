# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**ZertixPOS** is an ERP with integrated point of sale for distribution and retail businesses (Dominican Republic market). It manages sales, inventory, accounting, clients, and POS terminals from a single platform, including NCF (Dominican fiscal receipt) compliance.

## Quick Start

**Stack:** Laravel 12 + Livewire 4 + Tailwind + Vite + MySQL/SQLite

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

### Docker Development
```bash
./vendor/bin/sail up                 # Start Laravel Sail
./vendor/bin/sail shell              # SSH into container
```

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

## Architecture Overview

See `ARCHITECTURE.md` for the complete layered architecture pattern. Here's how the pieces fit:

### Module Structure (ERP Pattern)

The codebase follows a "Skinny Controllers" pattern with clear separation of concerns:

1. **Database Layer (`app/Models/`)**
   - Each module has models (e.g., `Products/Product.php`, `Sales/Invoice.php`)
   - Each model defines `scopeWithIndexRelations()` to centralize eager loading for both UI tables and Excel exports
   - All business entities use `SoftDeletes` for trash management

2. **Table Configuration (`app/Tables/`)**
   - Centralizes column names, labels, and visibility logic (`ProductTable.php`, `InvoiceTable.php`)
   - Methods: `allColumns()` (all available), `defaultDesktop()`, `defaultMobile()` (visible by default)
   - Separated by concern: `ProductTable.php`, `SalesTables/InvoiceTable.php`, etc.

3. **Request Validation & Authorization (`app/Http/Requests/`)**
   - FormRequest classes handle both validation (`rules()`) and permission checks (`authorize()`)
   - Pattern: `StoreProductRequest`, `UpdateProductRequest`, `BulkProductRequest`
   - All Spatie permission checks happen here before controller execution

4. **Query Filtering (`app/Filters/`)**
   - Pipeline pattern using a base `QueryFilter` class
   - Each filter is independent: `ProductsSearchFilter`, `ProductsActiveFilter`, `ProductsCategoryFilter`
   - Applied in controller via: `(new ProductsFilters($request))->apply(Product::query())`
   - Keeps controllers clean of `where` clauses

5. **Service Layer (`app/Services/`)**
   - **CatalogService** (`ProductCatalogService`): Supplies filtered data for dropdowns/selects (respects country_id, active status, etc.)
   - **Business Service** (`ProductService`): Handles creation, updates, bulk actions, transactions
   - Services manage `DB::transaction()` and complex logic; controllers never do `Model::create()` directly
   - Pattern: `performBulkAction($ids, $action, $value)` for batch operations

6. **HTTP Layer (`app/Http/Controllers/`)**
   - Pure orchestrators: receives request → calls services → returns view/JSON
   - Grouped by module: `Products/`, `Sales/` (with `Sales/Pos/` and `Sales/Ncf/` sub-namespaces), `Clients/`, `Accounting/`, `Inventory/`
   - Trait `SoftDeletesTrait` adds soft delete methods: `index` includes trash, `eliminadas()`, `restaurar()`, `borrarDefinitivo()`

7. **Routes (`routes/admin/`)**
   - Organized by module with RESTful conventions
   - Middleware applied per route: `permission:view products`, `permission:edit products`
   - Pattern: index, create, store, edit, update, bulk, destroy, plus trash routes

8. **Views (`resources/views/`)**
   - Blade templates organized by module
   - Table rendering with dynamic columns, AJAX filtering (chips-based UI)
   - Forms populated from `CatalogService`
   - Livewire component classes live in `app/Livewire/<Module>/` (e.g. `app/Livewire/Sales/Pos/`), with matching Blade views in `resources/views/livewire/<module>/`

### Key Traits

- **`HandleStorage`** (`app/Traits/HandleStorage.php`): Image/file upload handling with automatic cleanup
- **`SoftDeletesTrait`** (`app/Traits/SoftDeletesTrait.php`): Soft delete management (trash views, restore, permanent delete)

### Helpers

- **`general_config()`**: Cached access to `ConfiguracionGeneral` singleton (currency, logo, etc.)
- **`pos_helper.php`**: POS-specific utilities

### Permission System

- **Spatie Laravel Permission**: Roles and permissions seeded in `DatabaseSeeder`
- Permission naming convention: `view module`, `create module`, `edit module`, `delete module`, `restore module`
- Middleware applied per route: `middleware('permission:view products')`
- Custom validation in FormRequests using `$this->user()->can('...')`

### Frontend Stack

- **Tailwind CSS** + **@tailwindcss/forms** for styling
- **Alpine.js** for reactive components
- **Livewire 4** for real-time UI (e.g., POS terminal, pos-sessions)
- **Vite** for asset bundling with hot reload
- Tables rendered server-side with AJAX pagination/filtering

## Module Domains

| Domain | Controllers | Key Models | Purpose |
|--------|-------------|-----------|---------|
| **Products** | `ProductController`, `CategoryController`, `UnitController` | `Product`, `Category`, `Unit` | Catalog management |
| **Inventory** | `InventoryMovementController`, `InventoryStockController`, `WarehouseController` | `InventoryStock`, `InventoryMovement` | Stock tracking and movements |
| **Sales** (`Sales/`) | `InvoiceController`, `QuoteController`, `SaleController` | `Invoice`, `Quote`, `QuoteItem`, `Sale`, `SaleItem`, `SalePayment` | Quoting, invoicing, and sale order processing |
| **Sales → POS** (`Sales/Pos/`) | `PosTerminalController`, `PosSessionController`, `PosTerminalLockController`, `PosCashMovementController`, `PosConfigController` | `PosTerminal`, `PosSession`, `PosSetting`, `PosCashMovement` | Terminal sessions, cash drawer movements, PIN-gated access |
| **Sales → NCF** (`Sales/Ncf/`) | `NcfSequenceController`, `NcfTypeController`, `NcfLogController`, `NcfDashboardController` | `NcfSequence`, `NcfType`, `NcfLog` | Dominican fiscal receipt (NCF) compliance — sequence assignment and audit log |
| **Accounting** | `AccountingAccountController`, `JournalEntryController`, `ReceivableController`, `CollectionController` | `Account`, `Journal`, `Receivable`, `ClientCollection` | Double-entry accounting |
| **Clients** | `ClientController`, `EquipmentController`, `PointOfSaleController` (client-side POS reg.) | `Client`, `Equipment` | Customer management, credit limits, field equipment |
| **Configuration** | `ConfiguracionGeneralController`, `TipoPagoController`, `EstadosClienteController` | `ConfiguracionGeneral`, `TipoPago`, etc. | System-wide settings (currency, tax, payment types) |

## Important Architectural Decisions

1. **Eager Loading Centralization**: Models define `scopeWithIndexRelations()` so both index views and exports use the same relations—prevents N+1 problems and ensures consistency.

2. **No Business Logic in Controllers**: Services handle all writes, transactions, and complex calculations. Controllers are thin request/response handlers.

3. **FormRequest Authorization**: Permission checks happen at the request level, not in the controller. If `authorize()` returns false, the request is rejected before the controller method runs.

4. **Pipeline Filters**: Query building is declarative and composable. Each filter is a class; the pipeline applies them in sequence. Keeps controllers from becoming filter-heavy.

5. **Soft Deletes Everywhere**: All entities are soft-deleted by default. A dedicated `SoftDeletesTrait` provides `eliminadas()`, `restaurar()`, and `borrarDefinitivo()` methods on the controller.

6. **Transactions in Services**: Database transactions wrap all write operations in services, ensuring atomicity for complex operations like invoice creation (header + lines + GL entries).

7. **POS Security**: Terminal sessions require PIN validation (RateLimiter enforced), auto-lock on timeout, and mandatory pin for closing cash box. See `AppServiceProvider` for rate limiting and `PosSetting` for security flags.

8. **Bulk Actions Pattern**: Handled via a dedicated `BulkProductRequest` and `performBulkAction()` method in the service, allowing mass updates (activate, change category, delete) in a single transaction.

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

## Common Patterns

### Adding a New Module

1. **Create the model** with `SoftDeletes` and `scopeWithIndexRelations()`
2. **Create the Table class** with `allColumns()`, `defaultDesktop()`, `defaultMobile()`
3. **Create filter classes** in `app/Filters/YourModule/`
4. **Create FormRequest classes** (Store, Update, Bulk) with `authorize()` and `rules()`
5. **Create services** (CatalogService, YourModuleService) with `create()`, `update()`, `performBulkAction()`
6. **Create controller** using services, FormRequests, and Filters
7. **Register routes** in `routes/admin/yourmodule/`
8. **Create Blade views** (index, create, edit) using the Table class and catalog data
9. **Add permissions** in a PermissionSeeder and link to roles
10. **Run migrations and seeders**

See `ARCHITECTURE.md` for a complete implementation checklist.

## Notable Features

- **Excel Export**: `app/Exports/` classes powered by Maatwebsite; uses model's `scopeWithIndexRelations()` for consistency
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

Feature branches (`feat/<name>`) are merged to `develop` then `main` via pull requests.

